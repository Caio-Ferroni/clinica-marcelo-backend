# Análise do Backend — Clínica Marcelo

> [!NOTE]
> Esta análise cobre Config, Models, Controllers, Auth, Routes e Views. Atualizado em 15/11/2026 com a implementação do módulo Web Push (B21–B24), mantendo os disparos desativados. Os demais itens da seção 4.1 já foram tratados nas etapas anteriores. A integração visual, a configuração do servidor e a homologação real das notificações permanecem pendentes. Guia: [docs/webpush.md](docs/webpush.md).

---

## 1. Visão Geral da Arquitetura

O projeto segue uma estrutura **MVC manual** (sem framework pesado), centralizado na experiência de uso exclusivo do psicólogo:

| Camada | Localização | Responsabilidade | Status |
|---|---|---|---|
| **Config** | `Backend/Config/Database.php` | Singleton PDO para conexão MySQL | ✅ Ativo |
| **Models** | `Backend/Models/*.php` | Queries SQL diretas com PDO Prepared Statements | ✅ Atualizados |
| **Controllers** | `Backend/Controllers/*.php` | Recebe requisições, valida e delega para Models | ✅ Em expansão |
| **Routes** | `Backend/Routes/routes.php` | Mapeamento de rotas com Bramus Router | ✅ Criado |
| **Entry point** | `index.php` | Carrega autoload, models, controllers e executa o router | ✅ Estruturado |
| **Servidor Web** | `.htaccess` | Regras de rewrite para encaminhar URLs amigáveis ao `index.php` | ✅ Criado |
| **Auth** | `Backend/Auth/Auth.php` | Sessões, proteção de rotas e verificação de 2FA em memória | ✅ Concluído |
| **Notificações** | `Backend/Notifications/`, `Backend/Cron/`, `sw.js` | Módulo reutilizável para lembretes, API, persistência e integração JavaScript | ✅ Implementado; integração visual e ativação pendentes |
| **Views** | `Views/**/*.php` / `.html` | Telas do sistema (gerenciadas por outra frente) | ⏳ Visualização e carregamento da edição de paciente conectados; demais integrações pendentes |
| **Layout (Web Components)** | `js/layout.js` | Sidebar, footer e paginação de tabelas via Custom Elements | ✅ Funcional |

---

## 2. Regras de Negócio e Decisões de Projeto

| Regra | Descrição | Status |
|---|---|---|
| **Criação Centralizada de Pacientes** | O cadastro de paciente reúne Paciente, Perfil, Encaminhamento e Agendamento em um único formulário. Paciente é obrigatório; Perfil e Agendamento exigem todos os seus campos; Encaminhamento exige ao menos um. | ✅ Concluído |
| **Atualização em Cascata (Upsert)** | Atualizar o paciente também atualiza ou cria (caso ainda não existam) seus registros de Perfil, Agendamento e Encaminhamento via relação 1-1 baseada no `id_paciente`. | ✅ Concluído |
| **Preservação de Dados (Soft Delete / Status)** | Pacientes nunca são deletados fisicamente do banco de dados; são marcados como inativos e ocultados das listagens até eventual reativação. | ⏳ Filtro de ativos no model pronto; troca de status e integração da listagem pendentes |
| **Conta Única do Psicólogo** | Apenas um psicólogo atua na clínica. O registro de conta só é permitido uma única vez. Registros subsequentes são sumariamente bloqueados. | ✅ Concluído |
| **Autenticação em Dois Fatores (2FA)** | O login exige e-mail + senha e, em seguida, validação de código numérico temporário de 6 dígitos gerado e conferido via memória de sessão (`$_SESSION`), disparado por e-mail. | ✅ Concluído |
| **Notificação de Consulta a 10 Minutos** | Nome e horário para todos os dispositivos inscritos, inclusive após logout; recuperar atrasos somente até o início. | ✅ Módulo pronto; desativado por padrão |

---

## 3. Histórico de Alterações Realizadas

### 3.1 Correções Críticas (Bugs & Segurança)
1. **Lógica de validação `!empty()`**: Corrigido o agrupamento `&&` indevido que estava dentro da função `empty()` em `PacienteController.php` e `TarefaController.php`.
2. **Inversão de parâmetros**: Corrigida a ordem de `telefone` e `data_nascimento` passada de `PacienteController.php` para `Paciente.php`.
3. **SQL Injection eliminado**: Sanitizado o método `atualizarPerfil` em `Perfil.php`, substituindo interpolação direta por bind `:id` / `:id_paciente`.
4. **Typos corrigidos**: Corrigido erro de digitação de coluna `id_agedamento` e strings em `Agendamento.php`.
5. **Geração de consultas**: Corrigida chamada invertida de `$mes/$ano`, formatação de string ISO de datas e declaração estática do método auxiliar em `Consulta.php`.

### 3.2 Cadastro Centralizado e Upserts
- **`Paciente.php`**: `criarPaciente()` ajustado para retornar o `id_paciente` via `lastInsertId()`. Implementado `atualizarPaciente()`.
- **`Perfil.php`**: `atualizarPerfil()` refatorado para operar por `id_paciente` com verificação de existência (upsert).
- **`Encaminhamento.php`**: Implementado `atualizarEncaminhamento()` com verificação de existência (upsert).
- **`Agendamento.php`**: Corrigido nome de variável `$horario_inicio` e implementado `atualizarAgendamento()` com verificação de existência (upsert).
- **`PacienteController.php`**:
  - `criar()`: Valida obrigatoriedade estrita do Paciente; cria condicionalmente Perfil e Agendamento (se todos os campos preenchidos) e Encaminhamento (se ao menos um campo preenchido).
  - `atualizar($id)`: Aplica a mesma regra condicional com upsert nas entidades associadas.

### 3.3 Roteamento e Entry Point
- **`.htaccess`**: Criado com regras de rewrite para Apache, redirecionando requisições ao `index.php`.
- **`index.php`**: Transformado em front controller oficial da aplicação (sessão, autoload, models, controllers e execução do router).
- **`Backend/Routes/routes.php`**: Mapeamento inicial das rotas RESTful para Pacientes, Consultas, Tarefas, Perfil, Relatórios e Auth com Bramus Router.

### 3.4 Autenticação Completa com Conta Única e 2FA
- **`Backend/Models/Psicologo.php`**: Implementado `existePsicologo()` para garantir conta única, `criarPsicologo()` com `password_hash()`, e `fazerLogin()` com `password_verify()`. Corrigida query de listagem.
- **`Backend/Auth/Auth.php`**: Implementado gerenciamento de sessão, geração e conferência de código 2FA em memória (`$_SESSION`), disparo de e-mail/log, middleware de proteção `Auth::verificar()` e `Auth::logout()`.
- **`Backend/Controllers/PsicologoController.php`**: Métodos `formLogin()`, `login()`, `form2fa()`, `verificar2fa()`, `formRegistrar()`, `registrar()`, `logout()` e `perfil()`.
- **`Backend/Routes/routes.php`**: Ativado o middleware `Auth::verificar()` protegendo todas as rotas privadas.

### 3.5 Correção dos Bugs Críticos da Seção 4.1 — 14/11/2026

- **B1–B4**: Corrigida a capitalização de `Config/Database.php` nos quatro includes apontados.
- **B5**: `Consulta::gerarRelatorio()` passou a executar `UPDATE consulta SET relatorio = :relatorio WHERE id_consulta = :id`, mantendo os parâmetros preparados.
- **B7 e B10**: `PacienteController` carrega o paciente individual e suas associações por `id_paciente`; a visualização usa `paciente-show.php` e a rota de edição chama `editar($id)`. Pacientes inexistentes retornam HTTP 404.
- **Views de paciente**: A visualização exibe os dados carregados com escape HTML; a edição recebe os valores existentes e seleciona as opções correspondentes. Associações ausentes são tratadas como vazias.
- **B9 e B14–B19**: Corrigidas as referências às views PHP na criação de paciente e nos fluxos de login, registro, 2FA e perfil.
- **Validação realizada**: Sintaxe dos 11 arquivos PHP alterados, existência e capitalização dos caminhos e 19 testes isolados com SQLite em memória passaram. A execução com MySQL real permanece pendente; o driver PDO MySQL não está carregado no PHP local.
- **Limite desta entrega**: A seção 4.2 não foi implementada. O envio dos formulários, a busca geral de pacientes e as listagens dinâmicas permanecem pendentes; `login.php` e `register.php` continuam vazios. Notificações, inativação e os demais itens da tabela 4.1 também não foram implementados.

### 3.6 Correção dos Demais Itens Autorizados da Seção 4.1 — 14/11/2026

- **B6**: Nesta etapa, o controller passou a usar `horario_termino`, com `horario_fim` como alias, preservando domingo (`dia_semana = 0`). O alias foi removido na revisão da seção 3.7.
- **B8**: Cadastros com campos obrigatórios ausentes retornam HTTP 422 ao formulário de criação, com mensagem, valores preservados e escape HTML, sem gravar registros. No sucesso, o controller carrega a visualização do paciente criado.
- **B11**: Criado `Views/Tarefa/tarefa-create.php` com título, conteúdo e data de conclusão. GET e POST `/tarefas/criar` usam o controller; erros retornam HTTP 422 e preservam valores. Sucesso redireciona para `/tarefas` com HTTP 303. A listagem de tarefas ainda usa dados estáticos.
- **B12 e B13**: As rotas de relatórios carregam as views PHP existentes. A integração dos dados de relatórios permanece pendente na seção 4.2.
- **B20**: Excluído apenas o arquivo vazio `Backend/Auth/2fa.php`; a tela `Views/Auth/2fa.php` e a lógica de `Auth.php` foram preservadas.
- **B25**: O entry point inclui `NotificacaoController.php` com buffer local para descartar a quebra de linha do arquivo vazio e não antecipar o envio dos headers. O controller continua sem implementação, conforme a exclusão de B21 do escopo.
- **B26 e B27**: Removida a abertura duplicada de `<html>` em agendamento; `Paciente::listarTodos()` passou a filtrar `status = 'Ativo'`, sem excluir ou alterar registros. A troca de status não foi conectada.
- **Validação realizada**: Sintaxe PHP, caminhos e estrutura HTML verificados. Passaram 28 testes novos e os 19 testes anteriores, com SQLite em memória. Os arquivos de B21–B24 e a tela de 2FA foram conferidos por hash e permaneceram intactos. A validação com MySQL real segue pendente.
- **Limite desta entrega**: Não foram implementados Web Push, inativação/reativação, listagens dinâmicas ou a integração geral dos formulários da seção 4.2.

### 3.7 Alinhamento dos Campos de Paciente — 14/11/2026

- Os formulários de cadastro e edição usam diretamente os nomes do backend nos atributos `name`, incluindo `id_paciente` na edição. Foram removidos os mapas de tradução e os aliases.
- Os dois formulários incluem `horario_termino`. As opções usam dias de 0 a 6, `Semanal`/`Quinzenal`/`Mensal`, `Ativo`/`Inativo` e `Recebimento`/`Envio`.
- O cadastro preserva os valores após erro, escapa a saída com `htmlspecialchars()` e reabre as seções preenchidas. O controller filtra valores não escalares antes de devolver os campos à view.
- A pesquisa em `layout.js` lê `id_paciente`, `nome` e os demais campos diretamente, sem aceitar os nomes antigos.
- **Validação realizada**: Sintaxe PHP/JavaScript, os 47 cenários locais anteriores com expectativas atualizadas, verificação dos campos/opções renderizados, sete dias da semana, seções parciais/vazias, escape HTML e pesquisa/preenchimento em JavaScript. Banco de testes SQLite em memória; sem validação em navegador ou MySQL real.
- **Limite desta entrega**: O envio real continua pendente: os formulários permanecem sem `method`/`action`, com `preventDefault()`, e a edição mantém o botão inicialmente desabilitado. O JSON de pesquisa continua vazio; não foram adicionadas dependências, APIs ou dados fictícios à aplicação.

---

### 3.8 Módulo Web Push — 15/11/2026

- **B21–B24**: Implementados controller JSON autenticado com CSRF, cadastro/desativação de inscrições, remetente com VAPID, seleção de lembretes via CLI e service worker. A API está registrada; as telas não carregam o novo `js/webpush.js` automaticamente.
- Adicionadas migração e reversão manuais para duas tabelas, variáveis de ambiente e gerador CLI de chaves. A conexão PDO aceita opcionalmente lançar erros para o módulo tratar indisponibilidade sem encerrar uma resposta JSON; chamadas existentes preservam seu comportamento.
- Datas interpretadas em São Paulo, consultas `Pendente` de pacientes `Ativo`, recuperação até o início, revalidação antes do envio, histórico por dispositivo/horário, trava MySQL e tratamento de falhas/inscrições expiradas. Mais de uma conta de psicólogo interrompe o agendador até existir vínculo de responsabilidade por consulta.
- A estrutura do MySQL local foi consultada sem alterar registros. A validação usa banco temporário separado, biblioteca real com transporte HTTP simulado e simulação JavaScript do navegador/service worker. Comandos e limites em [docs/webpush.md](docs/webpush.md).
- **Pendente por escopo**: botões e integração nas telas, instalação PWA, aplicação da migração no banco da clínica, chaves do ambiente, agendamento no SO e homologação com dispositivos reais. Nenhuma notificação real foi enviada.

## 4. Bugs Encontrados e Problemas de Integração — Status Atual

> [!CAUTION]
> Os itens B1–B27 foram tratados; B20 foi resolvido pela remoção do arquivo redundante. B21–B24 têm módulo implementado, sem ativação no servidor ou integração nas telas. A severidade original é mantida como histórico. A seção 4.2 continua pendente; a validação MySQL desta etapa cobre somente o módulo Web Push em banco isolado.

### 4.1 Bugs Críticos no Backend

| # | Arquivo | Problema identificado / correção | Severidade original | Status atual |
|---|---|---|---|---|
| B1 | `Consulta.php` | Corrigido o include para `../Config/Database.php`, respeitando a capitalização da pasta. | 🔴 Crítico | ✅ Corrigido |
| B2 | `PacienteController.php` | Corrigido o include para `../Config/Database.php`. | 🔴 Crítico | ✅ Corrigido |
| B3 | `ConsultaController.php` | Corrigido o include para `../Config/Database.php`. | 🔴 Crítico | ✅ Corrigido |
| B4 | `TarefaController.php` | Corrigido o include para `../Config/Database.php`. | 🔴 Crítico | ✅ Corrigido |
| B5 | `Consulta.php` | `gerarRelatorio()` agora usa `UPDATE consulta SET relatorio = :relatorio WHERE id_consulta = :id`, com parâmetros preparados. | 🔴 Crítico | ✅ Corrigido |
| B6 | `PacienteController.php` | Padronizado apenas `horario_termino` na criação, atualização e nos formulários, sem alias `horario_fim`. Domingo (`dia_semana = 0`) é aceito. | 🟡 Média | ✅ Corrigido |
| B7 | `PacienteController.php` | `visualizar($id)` carrega o paciente e suas associações e renderiza `paciente-show.php` com dados reais e escape HTML. ID inexistente retorna HTTP 404. | 🔴 Crítico | ✅ Corrigido |
| B8 | `PacienteController.php` / `paciente-create.php` | Validação retorna HTTP 422 ao formulário de criação, com mensagem e valores preservados com escape HTML, sem gravação. Sucesso carrega a visualização do paciente criado. | 🟡 Média | ✅ Corrigido |
| B9 | `routes.php` | `GET /pacientes/criar` agora carrega `paciente-create.php`. | 🔴 Crítico | ✅ Corrigido |
| B10 | `routes.php` | `GET /pacientes/{id}/editar` chama `PacienteController::editar($id)`, carrega paciente, perfil, agendamento e encaminhamento e preenche os campos. ID inexistente retorna HTTP 404. | 🔴 Crítico | ✅ Corrigido |
| B11 | `routes.php` / `TarefaController.php` | GET e POST `/tarefas/criar` usam a nova view `tarefa-create.php`. O formulário valida dados obrigatórios e data, preserva valores nos erros e redireciona após salvar. | 🟡 Média | ✅ Corrigido |
| B12 | `routes.php` | `GET /relatorios` agora carrega `relatorio.php`. A integração dos dados segue pendente na seção 4.2. | 🟡 Média | ✅ Corrigido |
| B13 | `routes.php` | `GET /relatorios/{id}` agora carrega `relatorio-show.php`. O conteúdo da view continua incompleto. | 🟡 Média | ✅ Corrigido |
| B14 | `PsicologoController.php` | `formLogin()` agora carrega `login.php`. O conteúdo do formulário continua pendente na seção 4.2. | 🔴 Crítico | ✅ Corrigido |
| B15 | `PsicologoController.php` | Os retornos de erro de `login()` agora carregam `login.php`. | 🔴 Crítico | ✅ Corrigido |
| B16 | `PsicologoController.php` | `form2fa()` agora carrega `2fa.php`. | 🔴 Crítico | ✅ Corrigido |
| B17 | `PsicologoController.php` | Os retornos de erro de `verificar2fa()` agora carregam `2fa.php`. | 🔴 Crítico | ✅ Corrigido |
| B18 | `PsicologoController.php` | `formRegistrar()` e `registrar()` agora carregam `register.php`. O conteúdo do formulário continua pendente na seção 4.2. | 🔴 Crítico | ✅ Corrigido |
| B19 | `PsicologoController.php` | `perfil()` agora carrega `perfil-show.php`. | 🔴 Crítico | ✅ Corrigido |
| B20 | `Backend/Auth/2fa.php` | Arquivo vazio e redundante excluído. A lógica em `Auth.php` e a tela `Views/Auth/2fa.php` foram preservadas. | 🟢 Limpeza | ✅ Removido |
| B21 | `Backend/Controllers/NotificacaoController.php` | API de configuração, cadastro e remoção com sessão/2FA e CSRF. | 🟢 Pendente | ✅ Implementado; integração visual pendente |
| B22 | `Backend/Notifications/WebPushService.php` | Envio criptografado/VAPID, validação de destino e classificação de resultados. | 🟢 Pendente | ✅ Implementado; configuração pendente |
| B23 | `Backend/Cron/notificar_consultas.php` | CLI com janela de dez minutos, recuperação, deduplicação, trava e simulação. | 🟢 Pendente | ✅ Implementado; tarefa do SO pendente |
| B24 | `sw.js` | Exibição da mensagem e abertura de consultas; descarta payloads vencidos. | 🟢 Pendente | ✅ Implementado; registro nas telas pendente |
| B25 | `index.php` | Inclui o controller de notificações sem emitir conteúdo; o buffer do antigo arquivo vazio foi removido. | 🟡 Média | ✅ Corrigido |
| B26 | `Views/Agendamento/agendamento.html` | Removida a segunda abertura de `<html>`, mantendo uma única raiz HTML. | 🟡 Média | ✅ Corrigido |
| B27 | `Paciente.php` | `listarTodos()` filtra `status = 'Ativo'`. Não altera nem exclui registros e não conecta a troca de status ou a listagem visual. | 🟡 Futura | ✅ Corrigido |

### 4.2 Incompatibilidades entre Views (Frontend) e Backend

> [!WARNING]
> Os nomes e valores dos campos de cadastro e edição de paciente já coincidem com o backend. O envio dos formulários pela interface continua bloqueado e as demais integrações abaixo permanecem pendentes.

#### 4.2.1 Formulário de Cadastro de Paciente (`paciente-create.php`)

| Campo da View (`name`) | Campo esperado pelo Controller (`$_POST`) | Status |
|---|---|---|
| `nome` | `nome` | ✅ Alinhado |
| `data_nascimento` | `data_nascimento` | ✅ Alinhado |
| `cpf` | `cpf` | ✅ Alinhado |
| `telefone` | `telefone` | ✅ Alinhado |
| `dia_semana` | `dia_semana` | ✅ Alinhado |
| `repeticao` | `repeticao` | ✅ Alinhado |
| `horario_inicio` | `horario_inicio` | ✅ Alinhado |
| `horario_termino` | `horario_termino` | ✅ Alinhado |
| `data_inicio` | `data_inicio` | ✅ Alinhado |
| `quadro` | `quadro` | ✅ Alinhado |
| `descricao` | `descricao` | ✅ Alinhado |
| `tipo_encaminhamento` | `tipo_encaminhamento` | ✅ Alinhado |
| `historico_paciente` | `historico_paciente` | ✅ Alinhado |

A validação no controller já retorna o formulário com HTTP 422, mensagem e campos preservados quando recebe dados inválidos. A submissão pela interface ainda precisa das alterações abaixo.

Além disso:
- O `<form>` **não tem** `method="POST"` nem `action="/pacientes/criar"`.
- O JavaScript atual **impede o submit** (`event.preventDefault()`) e mostra uma mensagem de "não disponível".
Os `<option value>` já usam `dia_semana` de 0 a 6, `repeticao` com `Semanal`, `Quinzenal` ou `Mensal`, e `tipo_encaminhamento` com `Recebimento` ou `Envio`. O campo `horario_termino` foi adicionado.

#### 4.2.2 Formulário de Edição (`paciente-update.php`)

O carregamento inicial por `GET /pacientes/{id}/editar` foi corrigido em B10: os campos exibem o paciente e suas associações, com escape HTML. Os nomes agora seguem o backend, com `id_paciente` no campo oculto, `horario_termino` e status `Ativo`/`Inativo`. A pesquisa usa os mesmos nomes para buscar e preencher os campos. O envio continua pendente:

- Também **não tem** `method="POST"` nem `action="/pacientes/{id}/editar"`.
- O JavaScript também bloqueia o submit.
- O modal de busca de pacientes lê de um JSON embutido vazio (`[]`). O backend não injeta dados nele.

#### 4.2.3 Formulário de 2FA (`2fa.php`)

- `action` aponta para `../Dashboard/dashboard.html` em vez de `/2fa` (rota correta do backend).
- O formulário já possui `method="post"`; falta corrigir o destino do `action`.

#### 4.2.4 Formulários de Login e Registro

- `login.php` e `register.php` estão **vazios** (0 bytes). Sem HTML algum.

#### 4.2.5 Views de Listagem (Todas)

Todas as views de listagem (`paciente.php`, `consulta.php`, `tarefa.php`, `encaminhamento.php`, `perfil.php`, `relatorio.php`) possuem **dados estáticos hardcoded** (nomes fictícios, placeholders "XXXX"). Nenhuma faz loop PHP sobre dados do banco.

#### 4.2.6 Caminhos de CSS/JS nas Views

Todas as views usam caminhos relativos `../../css/` e `../../js/`. Isso funciona apenas se o servidor resolver o caminho relativo ao arquivo `.php`, **não** quando o roteador serve via `index.php` na raiz. Com Apache + `.htaccess`, as requisições a `/pacientes` vão para `index.php` e os caminhos relativos quebram. Devem ser caminhos absolutos: `/css/`, `/js/`.

#### 4.2.7 Links de Navegação na Sidebar

Os links do `layout.js` usam `href="#"` com `data-section` mas **não navegam para nenhuma rota**. Precisam apontar para as rotas reais (`/pacientes`, `/consultas`, `/tarefas`, `/relatorios`, `/perfil`, `/logout`).

---

## 5. Mapa de Arquivos — Status Atual

```
clinica-marcelo-backend/
├── index.php                                    ✅ Front controller funcional
├── .htaccess                                    ✅ Rewrite configurado
├── composer.json                                ✅ Router, e-mail, dotenv, Web Push e Guzzle
├── sw.js                                        ✅ Push e clique; integração visual pendente
│
├── Backend/
│   ├── Auth/
│   │   └── Auth.php                             ✅ Lógica de autenticação e 2FA
│   ├── Config/
│   │   └── Database.php                         ✅ Singleton PDO
│   ├── Controllers/
│   │   ├── PacienteController.php               ✅ Leitura, validação e horário corrigidos
│   │   ├── ConsultaController.php               ✅ B3 corrigido
│   │   ├── TarefaController.php                 ✅ Formulário e criação corrigidos
│   │   ├── PsicologoController.php              ✅ B14–B19 corrigidos; views de auth pendentes
│   │   └── NotificacaoController.php            ✅ API autenticada com CSRF
│   ├── Models/
│   │   ├── Paciente.php                         ✅ CRUD + filtro de ativos
│   │   ├── Perfil.php                           ✅ CRUD + upsert
│   │   ├── Encaminhamento.php                   ✅ CRUD + upsert
│   │   ├── Agendamento.php                      ✅ CRUD + upsert
│   │   ├── Consulta.php                         ✅ B1/B5 corrigidos
│   │   ├── Tarefa.php                           ✅ CRUD
│   │   └── Psicologo.php                        ✅ CRUD + conta única
│   ├── Notifications/
│   │   ├── WebPushService.php                   ✅ Transporte criptografado/VAPID
│   │   └── demais componentes                  ✅ Configuração, validação, repositório e lembretes
│   ├── Migrations/                              ✅ Instalação/reversão manuais do Web Push
│   ├── Cron/
│   │   └── notificar_consultas.php              ✅ CLI pronto; agendamento pendente
│   └── Routes/
│       └── routes.php                           ✅ Referências às views corrigidas
│
├── Views/
│   ├── Auth/
│   │   ├── login.php                            🔴 Vazio
│   │   ├── register.php                         🔴 Vazio
│   │   └── 2fa.php                              ⚠️ Action errado
│   ├── Paciente/
│   │   ├── paciente.php                         ⚠️ Dados hardcoded
│   │   ├── paciente-create.php                  ⚠️ Campos alinhados; envio pendente
│   │   ├── paciente-show.php                    ✅ Visualização com dados reais
│   │   └── paciente-update.php                  ⚠️ Dados carregados; envio pendente
│   ├── Consulta/
│   │   ├── consulta.php                         ⚠️ Dados hardcoded
│   │   └── consulta-show.php                    ⚠️ Incompleto
│   ├── Tarefa/
│   │   ├── tarefa.php                           ⚠️ Dados hardcoded
│   │   └── tarefa-create.php                    ✅ Formulário conectado ao controller
│   ├── Encaminhamento/
│   │   └── encaminhamento.php                   ⚠️ Dados hardcoded
│   ├── Perfil/
│   │   ├── perfil.php                           ⚠️ Dados hardcoded
│   │   └── perfil-show.php                      ⚠️ Incompleto
│   ├── Psicologo/
│   │   └── psicologo.php                        ⚠️ Incompleto
│   ├── Relatorio/
│   │   ├── relatorio.php                        ⚠️ Dados hardcoded
│   │   └── relatorio-show.php                   ⚠️ Incompleto
│   └── Agendamento/
│       └── agendamento.html                     ✅ HTML corrigido; dados estáticos
│
├── css/
│   ├── bootstrap.css / bootstrap.min.css        ✅
│   └── style.css                                ✅
└── js/
    ├── bootstrap.js / bootstrap.min.js          ✅
    └── layout.js                                ⚠️ Links não navegam
```

---

## 6. Próximos Passos — Plano de Correção e Integração

```mermaid
flowchart TD
    F1["Fase 1: Correção de Bugs no Backend\n(correções da seção 4.1 e módulo Web Push concluídos)"]
    F2["Fase 2: Integração Views de Auth\n(login.php, register.php, 2fa.php → backend)"]
    F3["Fase 3: Integração Views de Paciente\n(name attrs, form action/method, loop PHP)"]
    F4["Fase 4: Integração das demais Views\n(Consultas, Tarefas, Relatórios, Encaminhamento)"]
    F5["Fase 5: Navegação e UX\n(Sidebar links, caminhos absolutos CSS/JS, flash messages)"]
    F6["Fase 6: Integração e ativação Web Push\n(telas, migração, VAPID, cron e homologação)"]

    F1 --> F2 --> F3 --> F4 --> F5 --> F6
```

---

### Fase 1: Correção de Bugs no Backend

> [!IMPORTANT]
> As correções desta fase e o módulo Web Push foram implementados. A integração das views e a ativação dos lembretes permanecem nas etapas seguintes.

| Tarefa | Arquivo(s) | Bugs | Status |
|---|---|---|---|
| Corrigir caminhos `config` → `Config` (case-sensitive) | `Consulta.php`, `PacienteController.php`, `ConsultaController.php`, `TarefaController.php` | B1–B4 | ✅ Corrigido |
| Corrigir `gerarRelatorio()`: trocar `INSERT ... WHERE` por `UPDATE` | `Consulta.php` | B5 | ✅ Corrigido |
| Renderizar a visualização individual com dados reais | `PacienteController.php`, `paciente-show.php` | B7 | ✅ Corrigido |
| Corrigir referências às views de criação de paciente, autenticação e perfil | `PsicologoController.php`, `routes.php` | B9, B14–B19 | ✅ Corrigido |
| Carregar os dados na rota `GET /pacientes/{id}/editar` | `routes.php`, `PacienteController.php`, `paciente-update.php` e models associados | B10 | ✅ Corrigido |
| Corrigir as referências às views de tarefas e relatórios | `routes.php` | B11–B13 | ✅ Corrigido |
| Incluir `NotificacaoController.php` no `index.php` | `index.php` | B25 | ✅ Corrigido |

---

### Fase 2: Integração das Views de Autenticação

| Tarefa | Detalhes |
|---|---|
| **Criar conteúdo de `login.php`** | Formulário com `method="POST" action="/login"`, campos `name="email"` e `name="senha"`, exibição de `$erro` se definido. |
| **Criar conteúdo de `register.php`** | Formulário com `method="POST" action="/registrar"`, campos `name="email"`, `name="senha"`, `name="telefone"`, exibição de `$erro`. |
| **Corrigir `2fa.php`** | Mudar `action` de `../Dashboard/dashboard.html` para `/2fa`, manter `method="post"` e `name="codigo"`. |

---

### Fase 3: Integração das Views de Paciente

| Tarefa | Detalhes |
|---|---|
| **Alinhar `name` dos inputs com o backend** | ✅ Concluído no cadastro, edição e pesquisa JavaScript, sem mapas ou aliases. |
| **Adicionar campo `horario_termino`** | ✅ Concluído nos dois formulários. O controller aceita somente esse nome. |
| **Configurar o `<form>`** | `method="POST" action="/pacientes/criar"` no create. `method="POST" action="/pacientes/<?= $paciente['id_paciente'] ?>/editar"` no update. |
| **Remover `preventDefault()`** | Apagar o JavaScript que bloqueia o submit do formulário. |
| **Alinhar `<option value>` com o backend** | ✅ Concluído: dias de 0 a 6, repetição em português, status `Ativo`/`Inativo` e encaminhamento `Recebimento`/`Envio`. |
| **Implementar listagem dinâmica em `paciente.php`** | Usar `foreach ($pacientes as $p)` para gerar as `<tr>` com dados reais. Links de edição e visualização apontando para `/pacientes/{id}/editar` e `/pacientes/{id}`. |
| **Injetar dados JSON em `paciente-update.php`** | Pendente: popular o `<script id="patients-data">` com registros de chaves `id_paciente`, `nome` e os demais nomes do formulário, mantendo o escape adequado no JSON. |

---

### Fase 4: Integração das Demais Views

| View | Ação necessária |
|---|---|
| `consulta.php` | Loop PHP sobre `$consultas`, calcular contadores de status, links reais. |
| `tarefa.php` | Loop PHP sobre `$tarefas`, links de edição. |
| `encaminhamento.php` | Loop PHP sobre dados de encaminhamentos, contadores de tipo. |
| `perfil.php` | Loop PHP sobre perfis de pacientes. |
| `relatorio.php` / `relatorio-show.php` | Loop PHP, implementar `RelatorioController`. |
| `agendamento.html` → `.php` | Converter para PHP, loop sobre agendamentos. |

---

### Fase 5: Navegação e UX

| Tarefa | Detalhes |
|---|---|
| **Corrigir links da Sidebar** | Em `layout.js`, mudar `href="#"` para as rotas reais: `home` → `/pacientes`, `patients` → `/pacientes`, `appointments` → `/consultas`, `reports` → `/relatorios`, `tasks` → `/tarefas`, `profile` → `/perfil`, `logout` → `/logout`. |
| **Caminhos absolutos de assets** | Em todas as views, trocar `../../css/` por `/css/` e `../../js/` por `/js/` para funcionar com o router. |
| **Sistema de Flash Messages** | Criar helper `$_SESSION['flash']` nos controllers + renderização com Toastify.js nas views. |

---

### Fase 6: Integração e Ativação das Notificações Web Push

| Arquivo | Função no Sistema |
|---|---|
| `sw.js` *(raiz)* | Implementado: recebe push, valida vencimento e abre `/consultas`. |
| `Backend/Notifications/WebPushService.php` | Implementado: envio com VAPID e tratamento de resultados. |
| `Backend/Controllers/NotificacaoController.php` | Implementado: três rotas JSON registradas, com autenticação e CSRF. |
| `Backend/Cron/notificar_consultas.php` | Implementado: executar a cada minuto após configurar o servidor. |

**Tarefas a executar nesta etapa**:
1. Seguir [docs/webpush.md](docs/webpush.md) para instalar dependências no PHP do servidor e aplicar a migração manual no banco correto.
2. Gerar as chaves VAPID e configurar o `.env`; habilitar apenas após preparar o ambiente.
3. Conectar os controles de ativação/desativação nas telas autenticadas usando `js/webpush.js`; preparar instalação PWA quando necessária.
4. Homologar com dados fictícios e dispositivos reais, incluindo permissão negada, logout e múltiplos dispositivos.
5. Configurar o agendador do SO a cada minuto e acompanhar suas contagens de resultado.
