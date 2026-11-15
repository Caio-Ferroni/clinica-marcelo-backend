# Webpush: lembretes de consultas

O módulo avisa o psicólogo quando faltam até dez minutos para uma consulta. A entrega inclui API, persistência, remetente Web Push, script CLI, service worker e uma biblioteca JavaScript para os devs conectarem às telas. O envio vem **desativado**. Nenhuma tela carrega a biblioteca automaticamente; nenhuma tarefa do sistema operacional é criada pela aplicação.

## Avaliação do sistema e decisões

O sistema usa MVC manual, Bramus Router, sessões PHP com 2FA e MySQL/MariaDB. A estrutura local foi conferida sem alterar registros: `consulta` contém `id_consulta`, `id_agendamento`, `data`, `horario_inicio` e `status`, com padrão `Pendente`. O caminho até o nome é `consulta.id_agendamento → agendamento.id_paciente → paciente.nome`. Somente consultas `Pendente` de pacientes `Ativo` são elegíveis. O módulo não gera consultas: as ocorrências precisam existir em `consulta`.

A clínica tem **uma conta de psicólogo**. As inscrições pertencem ao identificador da sessão autenticada. O agendador entrega todas as consultas elegíveis aos dispositivos dessa conta. Se houver mais de uma conta no banco, ele interrompe a execução: para suportar vários profissionais, primeiro será necessário adicionar o vínculo de cada consulta ao seu responsável e substituir a seleção atual. Não basta liberar o cadastro de contas.

Decisões da entrega:

- Mostrar nome e horário do paciente na notificação, inclusive onde o sistema operacional a apresentar na tela bloqueada. Outros dados clínicos não são incluídos.
- Enviar a todos os dispositivos inscritos. Logout não cancela a inscrição. O botão de desativar atua somente no dispositivo atual.
- Executar a verificação no servidor a cada minuto. O envio começa na primeira execução em que faltarem até 600 segundos, nunca antes; normalmente isso representa entre nove e dez minutos de antecedência.
- Recuperar lembretes atrasados apenas antes do início. A mensagem informa os minutos restantes, arredondados para cima, e o horário da consulta.
- Não criar dependência de timer JavaScript, aba aberta ou sessão ativa para o disparo.

## Componentes e fluxo

| Componente | Responsabilidade |
| --- | --- |
| `Backend/Controllers/NotificacaoController.php` | Configuração pública da integração, cadastro e desativação de inscrições com autenticação e CSRF. |
| `Backend/Notifications/` | Configuração, validação, repositório, seleção de lembretes e transporte. |
| `Backend/Cron/notificar_consultas.php` | Entrada CLI para execução a cada minuto. Não aceita execução HTTP. |
| `Backend/Migrations/001_webpush_up.sql` | Tabelas novas; aplicação manual. |
| `js/webpush.js` | API `window.ClinicWebPush`, sem efeitos ao carregar o arquivo. |
| `sw.js` | Exibição de notificações e abertura de `/consultas`, sem cache de páginas ou dados de pacientes. |

O navegador pede permissão após clique explícito, cria uma inscrição com a chave pública VAPID e a envia à API. O servidor associa a inscrição à sessão. O CLI seleciona as ocorrências, revalida status/horário/dispositivo, registra a tentativa, criptografa o payload e envia ao serviço de push do navegador. O service worker exibe a notificação. O clique abre ou foca `/consultas`; se a sessão tiver terminado, a autenticação normal do site será exigida.

## Requisitos e instalação

- PHP **8.2 ou superior**, Composer, extensões `curl`, `mbstring`, `openssl` com P-256 e `pdo_mysql`. O Composer selecionará dependências compatíveis com o PHP usado na instalação: execute-o com a mesma versão do servidor.
- MySQL/MariaDB com InnoDB e permissão para `GET_LOCK`/`RELEASE_LOCK`. A conexão da aplicação continua sendo a de `Backend/Config/Database.php`.
- Site servido em HTTPS. `http://localhost` é uma exceção útil para desenvolvimento; um IP da rede em HTTP não equivale a localhost.
- Acesso de saída HTTPS aos serviços de push. Não desabilitar a verificação de certificados. O PHP do agendador deve ter as mesmas extensões, certificados e acesso ao `.env` que a aplicação web.

Instalar as dependências declaradas no projeto:

```sh
composer install --no-dev --no-interaction --no-plugins
composer check-platform-reqs --no-dev
```

São usados `minishlink/web-push:^11.0` e `guzzlehttp/guzzle:^7.9.2`. O cliente e as fábricas HTTP são fornecidos explicitamente, sem depender da execução do plugin de descoberta do Composer. O `.gitignore` existente não foi alterado; `vendor/` e `composer.lock` continuam seguindo a política atual do projeto.

No Windows, se o OpenSSL não localizar sua configuração, configure `OPENSSL_CONF` no ambiente do PHP usado pelo agendador. Exemplo para o PHP standalone:

```powershell
$env:OPENSSL_CONF = 'C:\php\extras\ssl\openssl.cnf'
```

Use o caminho da instalação de cada servidor. Não é necessário alterar o PHP global para testes: as extensões podem ser habilitadas em um arquivo de configuração próprio ou por opções `-d`.

### 1. Aplicar a migração manualmente

Com backup e no banco correto, execute o conteúdo de `Backend/Migrations/001_webpush_up.sql`, por exemplo pela aba SQL do phpMyAdmin. O script não cria nem altera pacientes, agendamentos, consultas ou psicólogos; requer que essas tabelas já existam. A execução repetida falha intencionalmente se as tabelas novas já existirem, evitando esconder uma instalação parcial.

| Tabela | Dados |
| --- | --- |
| `webpush_subscriptions` | Psicólogo, endpoint, hash único do endpoint, chaves da inscrição, validade e estado ativo. |
| `webpush_deliveries` | Consulta, dispositivo, início da ocorrência, tentativas, resultado HTTP e próxima tentativa. Não armazena nome nem corpo da mensagem. |

As chaves estrangeiras correspondem aos identificadores `INT` assinados existentes. `starts_at` usa a data/hora local de São Paulo, assim como a consulta. Validades, próxima tentativa e datas operacionais das novas tabelas usam UTC, independentemente do fuso da conexão MySQL.

### 2. Gerar e configurar as chaves

Execute uma vez por ambiente, em terminal privado:

```sh
php Backend/Notifications/gerar_chaves.php
```

Copie as duas linhas geradas para o `.env`, sem substituir outras configurações. Não copie a chave privada para JavaScript, documentos públicos ou logs. Complete:

```dotenv
WEBPUSH_ENABLED=false
WEBPUSH_VAPID_PUBLIC_KEY=<chave pública gerada>
WEBPUSH_VAPID_PRIVATE_KEY=<chave privada gerada>
WEBPUSH_VAPID_SUBJECT=mailto:contato@seu-dominio.example
```

Troque o contato pelo endereço real do responsável técnico; também é aceita uma URL HTTPS de contato. Não gere novas chaves em cada execução. A chave pública pode ser entregue ao navegador; a privada permanece no servidor. O módulo valida o formato e se as chaves formam um par.

### 3. Ativar após preparar banco, tela e ambiente

Defina `WEBPUSH_ENABLED=true`. Sem essa configuração, o CLI encerra sem conectar ao banco e a API recusa novos cadastros. A remoção de inscrições continua disponível para o psicólogo autenticado mesmo com o módulo desabilitado.

Não é necessário publicar endpoint público de disparo nem fornecer senha ao agendador: o disparo é exclusivamente CLI.

## Integração nas telas

Os devs escolhem a tela autenticada que terá os controles. A inicialização registra `/sw.js` com escopo `/`, mas não pede permissão. **Aguarde `init()` antes de habilitar o botão de ativação** e chame `enable()` diretamente no clique, para preservar a ação do usuário exigida pelos navegadores.

Exemplo de integração, a adaptar aos componentes visuais do projeto:

```html
<button id="ativar-lembretes" type="button" disabled>Ativar lembretes neste dispositivo</button>
<button id="desativar-lembretes" type="button" disabled>Desativar lembretes neste dispositivo</button>
<p id="estado-lembretes" role="status"></p>
<script src="/js/webpush.js"></script>
<script>
const ativar = document.getElementById('ativar-lembretes');
const desativar = document.getElementById('desativar-lembretes');
const estado = document.getElementById('estado-lembretes');
const push = window.ClinicWebPush;
let pronto = false;

function renderizar(s) {
    ativar.disabled = !pronto || !s.supported || !s.enabled;
    desativar.disabled = !pronto || !s.subscribed;
    estado.textContent = !s.supported ? 'Navegador incompatível ou conexão sem HTTPS.'
        : !s.enabled ? 'Os lembretes ainda não foram habilitados no servidor.'
        : s.subscribed ? 'Dispositivo inscrito. Os lembretes continuam após sair da conta.'
        : 'Ative para receber nome e horário da consulta, inclusive na tela bloqueada.';
}

ativar.addEventListener('click', () => {
    // Não inserir operações assíncronas antes desta chamada.
    const operacao = push.enable();
    ativar.disabled = desativar.disabled = true;
    operacao.then(renderizar).catch((erro) => { estado.textContent = erro.message; })
        .finally(async () => {
            const s = await push.state();
            ativar.disabled = !s.supported || !s.enabled;
            desativar.disabled = !s.subscribed;
        });
});

desativar.addEventListener('click', async () => {
    ativar.disabled = desativar.disabled = true;
    try { renderizar(await push.disable()); }
    catch (erro) { estado.textContent = erro.message; }
    finally {
        const s = await push.state();
        ativar.disabled = !s.supported || !s.enabled;
        desativar.disabled = !s.subscribed;
    }
});

(async () => {
    try {
        await push.init();
        await push.sync();
        pronto = true;
        renderizar(await push.state());
    } catch (erro) {
        // Manter os controles disponíveis para tentar novamente/renovar uma inscrição.
        pronto = true;
        const s = await push.state();
        ativar.disabled = !s.supported || !s.enabled;
        desativar.disabled = !s.subscribed;
        estado.textContent = erro.message;
    }
})();
</script>
```

| Método | Comportamento |
| --- | --- |
| `init()` | Busca configuração/CSRF e registra o worker se o módulo estiver habilitado e o navegador for compatível. |
| `state()` | Retorna `{supported, enabled, permission, subscribed}`. `subscribed` indica inscrição local, não confirmação de sincronização no servidor. |
| `enable()` | Deve partir do clique. Solicita permissão, cria/reutiliza a inscrição e salva no servidor. Uma nova inscrição é removida localmente se o cadastro falhar. |
| `sync()` | Reenvia uma inscrição existente, sem pedir permissão; usar na abertura de telas autenticadas. Não é necessário enquanto o site estiver fechado. |
| `disable()` | Desativa no servidor e cancela no navegador. Em caso de falha, mostrar o erro e permitir nova tentativa. |

Se a permissão for negada, a interface deve orientar o usuário a rever as permissões do site no navegador. Não repetir pedidos automaticamente. Se as chaves VAPID mudarem, `sync()` informa a necessidade de ativar novamente; `enable()` remove a inscrição antiga e cria outra. Abra novamente a tela após um novo login para obter o token CSRF da sessão atual.

Não chamar `disable()` no logout: manter notificações após sair foi uma decisão funcional. Em dispositivo compartilhado, orientar a desativação manual antes de sair. O módulo não fornece gerenciamento remoto de dispositivos nesta versão.

### iPhone/iPad e PWA

No iOS/iPadOS, o uso previsto é um web app adicionado à tela inicial em versão compatível, com solicitação de permissão após toque explícito. A integração de instalação PWA permanece com os devs das telas: oferecer manifest com nome, identificador estável, ícones, `start_url` e `display: standalone`, vincular o manifest à página e orientar “Adicionar à Tela de Início”. Esta entrega não inclui manifest, ícones ou alterações de layout.

Não é necessário aplicativo nativo nem conta Apple Developer para Web Push. Testar no aparelho real; detectar as APIs disponíveis, sem presumir suporte pelo nome do navegador. Se já houver outro service worker na publicação, consolidar os handlers de push/clique nele em vez de registrar workers concorrentes para o mesmo escopo.

## Contratos HTTP

Todas as rotas exigem sessão com `autenticado === true` e `psicologo_id` válido. A etapa pendente do 2FA não autoriza acesso. As respostas são JSON com `Cache-Control: no-store`. O middleware HTML existente é ignorado somente para esse prefixo, permitindo resposta `401` em JSON sem redirecionamento para login.

### `GET /notificacoes/config`

```json
{"enabled":true,"publicKey":"<chave pública VAPID>","csrfToken":"<token da sessão>"}
```

Desabilitado: `enabled:false` e `publicKey:null`. Chaves privadas nunca são retornadas. Configuração inválida quando habilitado retorna `503`.

### `POST /notificacoes/salvar-subscription`

Cabeçalhos: `Content-Type: application/json`, `X-CSRF-Token: <csrfToken>`. Enviar `PushSubscription.toJSON()` diretamente:

```json
{"endpoint":"https://fcm.googleapis.com/...","expirationTime":null,"keys":{"p256dh":"<chave do dispositivo>","auth":"<segredo do dispositivo>"}}
```

Não enviar `psicologo_id`; qualquer campo desse tipo é ignorado. Cadastro repetido atualiza a mesma inscrição e a reativa. O endpoint é único e não pode ser transferido para outra conta. `expirationTime`, quando presente, é um timestamp futuro em milissegundos. O formato de criptografia usado no envio é `aes128gcm`.

Aceitar somente HTTPS na porta padrão/443, sem credenciais ou fragmento, nos hosts `fcm.googleapis.com`, `updates.push.services.mozilla.com`, subdomínios de `push.apple.com` e subdomínios de `notify.windows.com`. Não aceitar hosts arbitrários/IPs internos. Outros provedores exigem revisão explícita da lista em `PushSubscriptionValidator`; não substituí-la por aceitação irrestrita. O remetente revalida o destino e não segue redirecionamentos.

### `POST /notificacoes/remover-subscription`

Mesmos cabeçalhos e corpo `{"endpoint":"<endpoint da inscrição>"}`. Desativa apenas a inscrição pertencente à conta da sessão, de forma idempotente. Não remove o histórico de entregas.

| Código | Significado |
| --- | --- |
| `200` | Operação concluída: `{"ok":true}` ou configuração. |
| `401` | Sessão ausente/incompleta. |
| `403` | Token CSRF ausente/incorreto. |
| `409` | Inscrição indisponível para essa conta. |
| `413` | Corpo acima de 16 KiB. |
| `415` | Tipo de conteúdo diferente de JSON. |
| `422` | JSON, endpoint, validade ou chaves inválidos. |
| `503` | Cadastro desabilitado, configuração inválida ou indisponibilidade da operação. |

Erros retornam `{"error":"mensagem"}` sem detalhes internos. O controller devolve `405` se chamado com método incompatível; métodos sem rota no Bramus podem resultar em `404`.

## Agendador

Primeiro verificar sem envio ou escrita de tentativas:

```sh
php Backend/Cron/notificar_consultas.php --dry-run
```

Com o módulo desativado, a saída será `Webpush desabilitado.`. Com ele ativo, `--dry-run` conta os pares consulta/dispositivo elegíveis no momento, incluindo os que já constam no histórico; não é uma previsão de quantos novos envios ocorreriam. Não exibe nomes, endpoints ou mensagens.

### Linux

Exemplo de cron, ajustando os caminhos absolutos e o usuário que lê o `.env`:

```cron
* * * * * /usr/bin/php /caminho/clinica/Backend/Cron/notificar_consultas.php >> /caminho-privado/webpush.log 2>&1
```

O script localiza dependências e `.env` pelo próprio caminho; não depende da pasta de trabalho do cron. Proteja o log e aplique rotação. A saída contém apenas contagens e mensagens genéricas.

### Windows

Criar manualmente uma tarefa no Agendador de Tarefas:

- Programa: caminho absoluto do PHP com as extensões necessárias, por exemplo `C:\xampp\php\php.exe`.
- Argumentos: `"C:\caminho\clinica\Backend\Cron\notificar_consultas.php"`.
- Iniciar em: `C:\caminho\clinica`.
- Repetição: a cada **1 minuto**, indefinidamente, mesmo sem sessão interativa; não iniciar uma segunda instância se a anterior estiver executando.
- Conta da tarefa com leitura do projeto, `.env`, configuração OpenSSL/certificados e acesso ao MySQL.

Não copiar o exemplo de caminho sem conferir o PHP usado. O PHP padrão desta máquina não carrega todas as extensões; os testes usam uma configuração explícita.

### Resultados e falhas

O CLI retorna `0` quando conclui, está desativado ou outra execução já possui a trava; `1` para falha de execução/configuração; `2` quando houve falha temporária ou permanente de envio. O próximo ciclo deve continuar rodando mesmo após erro. O retorno JSON contém contagens `eligible`, `sent`, `temporary`, `expired`, `permanent`, `skipped` e `locked`.

`GET_LOCK` é específico do banco e permanece na conexão durante toda a execução, com liberação em `finally` ou quando a conexão termina. A chave única do histórico é `(id_consulta, subscription_id, starts_at)`. Sucessos não são reenviados; falhas de um dispositivo não provocam reenvio nos demais.

- Somente HTTP 2xx confirma aceitação pelo serviço de push.
- `404`/`410`: marcar a inscrição inativa.
- Erros de conexão, `408`, `425`, `429` e `5xx`: tentar novamente depois de pelo menos 60 segundos, respeitando `Retry-After` numérico ou data HTTP.
- Outros resultados, inclusive redirecionamento: falha permanente daquela ocorrência/dispositivo. Verificar chaves/serviço e, após corrigir, renovar a inscrição quando necessário.
- Cancelamentos, conclusões, inativação do paciente, inscrição removida/expirada e mudanças de horário são revalidados antes do envio. Uma consulta remarcada é tratada pelo novo horário.

Não há promessa de entrega exatamente aos dez minutos: rede, navegador, economia de energia e configurações do aparelho influenciam a exibição. A aceitação pelo provedor não é confirmação de leitura. O TTL termina no início da consulta e o worker ignora payloads vencidos pelo relógio do dispositivo. Uma interrupção após aceitação pelo provedor e antes da gravação local pode causar repetição; `topic`, `tag` estáveis e `renotify:false` reduzem o efeito, sem garantir entrega exatamente uma vez.

Uma notificação já entregue não pode ser revogada com segurança após cancelamento ou reagendamento. Use o horário na própria mensagem e confira a agenda atual ao abri-la. Mantenha os relógios do servidor e dos dispositivos sincronizados.

## Validação, manutenção e reversão

Testes sem notificações reais:

```sh
php tests/webpush.php
node tests/webpush-browser.cjs
node tests/webpush-http.cjs
```

O teste PHP cria um banco aleatório `codex_webpush_test_<identificador>`, executa as migrações e remove esse banco no `finally`. Requer usuário de teste com permissão de criar/remover bancos. Não lê nem altera os registros da clínica. Aceita `WEBPUSH_TEST_HOST`, `WEBPUSH_TEST_PORT`, `WEBPUSH_TEST_USER` e `WEBPUSH_TEST_PASSWORD`. As credenciais padrão são localhost:3306, root sem senha, apenas para desenvolvimento local. Nunca executar com credenciais de produção. Se o processo for encerrado à força, o banco temporário pode permanecer; conferir o nome antes de removê-lo manualmente.

Nesta máquina:

```powershell
$env:OPENSSL_CONF = 'C:\php\extras\ssl\openssl.cnf'
php -c .local/php.ini -d extension=pdo_mysql tests/webpush.php
node tests/webpush-browser.cjs
$env:WEBPUSH_TEST_PHP_INI = (Resolve-Path .local/php.ini).Path
node tests/webpush-http.cjs
```

O PHP exercita a persistência MySQL, migração/reversão, propriedade da inscrição, CSRF/2FA, janela temporal, virada do dia, atrasos, reagendamento, deduplicação, múltiplos dispositivos, trava entre conexões, validade e classificação de falhas. O transporte HTTP simulado passa pela biblioteca real de criptografia/VAPID, sem acessar os provedores. O teste JavaScript simula navegador e service worker; não substitui homologação em aparelho real. O teste HTTP inicia um servidor PHP temporário em localhost com o módulo desligado, cria sessões isoladas e verifica as rotas e o middleware reais, sem consultar o banco. Aceita `WEBPUSH_TEST_PHP` para o executável e `WEBPUSH_TEST_PHP_INI` para o arquivo de configuração; encerra o servidor e remove as sessões ao concluir.

Antes de ativar em produção, homologar em ambiente de testes com conta e pacientes fictícios: permissão concedida/negada, tela fechada, logout, dois dispositivos, cancelamento, reagendamento, expiração, erro de rede, clique e iPhone/iPad instalado. Essa verificação real não foi executada nesta entrega.

Não registrar corpo de notificação, objetos de inscrição ou exceções HTTP completas: podem conter nome, endpoint e chaves. Restrinja acesso ao banco e aos backups. O histórico não tem limpeza automática nesta versão; a equipe pode definir retenção e remover entregas antigas por `starts_at`, preservando as futuras. Inscrições desativadas continuam armazenadas para permitir reativação idempotente.

Para parar os disparos, definir `WEBPUSH_ENABLED=false` e desabilitar a tarefa do SO. Para remover as tabelas, executar manualmente `Backend/Migrations/001_webpush_down.sql` no banco correto; isso apaga inscrições e histórico, exigindo nova ativação dos dispositivos se reinstalado. A remoção local de uma inscrição pode ser feita pelas configurações do navegador. Nenhum outro dado do sistema é removido.

### Referências

- [Biblioteca Web Push PHP e requisitos](https://github.com/web-push-libs/web-push-php)
- [Mudanças de interface na versão 11](https://github.com/web-push-libs/web-push-php/releases/tag/v11.0.0)
- [MDN: Push API e cuidados com inscrições](https://developer.mozilla.org/en-US/docs/Web/API/Push_API)
- [WebKit: Web Push no iOS/iPadOS](https://webkit.org/blog/13878/web-push-for-web-apps-on-ios-and-ipados/)
