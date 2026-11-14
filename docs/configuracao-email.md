# Configuração do e-mail de 2FA com Zoho Mail

O projeto usa PHPMailer para enviar o código de verificação pelo SMTP do Zoho. As configurações são carregadas do arquivo `.env` pelo phpdotenv, na entrada `index.php`. Cada desenvolvedor deve usar suas próprias configurações locais.

## 1. Preparar o PHP e as dependências

Use PHP 8 ou superior com OpenSSL habilitado. Na pasta do projeto, execute:

```sh
php --ini
php -r "var_export(extension_loaded('openssl'));"
composer install
```

O segundo comando deve retornar `true`. Se retornar `false`, habilite `extension=openssl` no `php.ini` usado pelo site e reinicie o servidor. O PHP do terminal e o PHP do Apache/XAMPP podem carregar arquivos de configuração diferentes.

O Composer instala `phpmailer/phpmailer` e `vlucas/phpdotenv`, além do roteador existente. Se estiver usando `composer.phar`, substitua `composer` por `php caminho/para/composer.phar`.

O projeto mantém `composer.lock` ignorado pelo Git. Se seu arquivo local estiver desatualizado depois de atualizar o projeto, execute:

```sh
composer update phpmailer/phpmailer vlucas/phpdotenv --with-dependencies
```

## 2. Obter os dados no Zoho

1. Entre na conta que enviará os códigos.
2. Abra as configurações da conta de e-mail e localize os detalhes do servidor SMTP.
3. Copie o servidor indicado para sua conta e região. Os exemplos globais são `smtp.zoho.com` para contas pessoais/organizações gratuitas.
4. Use o endereço de e-mail completo como usuário SMTP.
5. Se a conta utiliza autenticação em duas etapas, gere uma senha específica de aplicativo no Zoho e use-a na configuração do projeto. Siga também eventuais políticas da organização.
6. Use como remetente a própria conta ou um alias autorizado para ela. Se utilizar domínio próprio, conclua a verificação e as configurações de autenticação do domínio orientadas pelo Zoho.

O endereço de destino é o e-mail do psicólogo cadastrado no sistema. Ele não precisa ser a mesma conta que envia os códigos.

## 3. Configurar o .env

Copie o modelo na raiz do projeto. No PowerShell:

```powershell
Copy-Item .env.example .env
```

Preencha o arquivo local:

```dotenv
SMTP_HOST=smtp.zoho.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=conta-remetente@seu-dominio.com
SMTP_PASSWORD='sua-senha-de-aplicativo'
MAIL_FROM_ADDRESS=conta-remetente@seu-dominio.com
MAIL_FROM_NAME="Clínica"
```

| Variável | Valor |
|---|---|
| `SMTP_HOST` | Servidor SMTP exato fornecido pelo Zoho; obrigatório. |
| `SMTP_PORT` | `587` para STARTTLS ou `465` para SMTPS. Padrão: `587`. |
| `SMTP_ENCRYPTION` | `tls` com porta `587`, ou `ssl` com porta `465`. Padrão: `tls`. |
| `SMTP_USERNAME` | Endereço completo da conta remetente; obrigatório. |
| `SMTP_PASSWORD` | Credencial aceita pelo Zoho, normalmente senha de aplicativo quando há 2FA; obrigatória. |
| `MAIL_FROM_ADDRESS` | A conta remetente ou um alias autorizado; obrigatório. |
| `MAIL_FROM_NAME` | Nome mostrado ao destinatário. Padrão: `Clínica`. |

Para usar a porta 465, altere também `SMTP_ENCRYPTION=ssl`. Esse nome seleciona SMTPS no PHPMailer; a validação dos certificados permanece habilitada. Conexão sem criptografia não é aceita.

Use aspas simples em senhas com espaços, `#` ou `$` para evitar interpretação pelo formato dotenv. Variáveis já configuradas no ambiente do servidor têm prioridade sobre o arquivo. Não coloque credenciais no código, na documentação ou no chat.

O `.env` já está ignorado pelo Git; somente `.env.example`, sem credenciais, deve ser compartilhado. A integração não cria nem preenche um `.env` automaticamente e não altera as configurações do banco.

## 4. Executar o site

No Apache 2.4, habilite `mod_rewrite` e permita as regras do `.htaccess` do projeto com `AllowOverride All`. Ele encaminha as rotas para `index.php` e bloqueia acesso HTTP a `.env` e suas variantes.

Se usar outro servidor, configure um bloqueio equivalente antes de disponibilizar o arquivo de credenciais. O servidor embutido `php -S` não interpreta `.htaccess`; não sirva a raiz do projeto sem um roteador que bloqueie esses arquivos e encaminhe as rotas corretamente.

Abra `/login` pelo servidor PHP. Não abra diretamente os arquivos de `Views/Auth`: o carregamento do `.env`, a sessão e os controllers passam pela entrada da aplicação.

## 5. Testar o fluxo

1. Configure uma conta remetente e use um psicólogo já cadastrado com endereço de e-mail acessível.
2. Entre com as credenciais do sistema em `/login`.
3. Quando o SMTP aceitar o envio, a aplicação deve abrir `/2fa`. Confira a caixa de entrada e o spam.
4. Digite um código incorreto: a tela deve mostrar o erro e manter o acesso protegido.
5. Digite o código recebido dentro de dez minutos: o sistema deve autenticar e abrir `/pacientes`. O código é removido da sessão e não pode ser reutilizado.
6. Em outra tentativa, espere mais de dez minutos: o código deve ser recusado. Volte ao login para solicitar um novo envio.
7. Em ambiente local, teste uma senha SMTP incorreta ou configuração obrigatória ausente. O login deve mostrar a indisponibilidade do envio, sem encaminhar para `/2fa` ou manter o código anterior utilizável.
8. Confirme que uma requisição HTTP a `/.env` não retorna seu conteúdo, sem copiar a resposta para logs ou mensagens.

Há timeout de conexão de 15 segundos e limite de espera de comandos SMTP. Uma nova tentativa de login substitui o desafio anterior; se o novo envio falhar, o código anterior também deixa de valer.

Aceitação pelo SMTP não confirma entrega à caixa de entrada. A validação final exige testar o recebimento na conta real. Os testes automatizados locais usam transporte simulado e não enviam mensagens ao Zoho.

## Diagnóstico

| Sintoma | Conferir |
|---|---|
| Mensagem de indisponibilidade no login | Consulte o log PHP do servidor. A aplicação registra a etapa da falha, sem códigos, senhas ou conteúdo SMTP. |
| Configuração ausente | Confira o nome da variável, o `.env` na raiz e o acesso pela entrada da aplicação. |
| Arquivo `.env` inválido | Confira aspas e sintaxe. O log informa o erro sem mostrar o conteúdo do arquivo. |
| OpenSSL ausente | Habilite a extensão no PHP usado pelo servidor e reinicie-o. |
| Falha de autenticação | Confira usuário completo, senha de aplicativo e políticas da conta Zoho. |
| Falha de conexão | Confira host/região, combinação porta/criptografia, DNS e liberação da porta SMTP no servidor. |
| Falha de certificado | Confira relógio do sistema e certificados de autoridades confiáveis do PHP. Não desabilite a validação TLS. |
| Remetente recusado / relaying disallowed | Use a conta autenticada ou um alias autorizado no Zoho. |
| E-mail aceito, mas não recebido | Confira spam, endereço cadastrado e os registros de envio do Zoho. Para domínio próprio, confira também a configuração de autenticação do domínio no provedor. |

O cadastro de conta continua seguindo a regra existente de um único psicólogo. Esta integração não altera o formulário de registro nem adiciona botão de reenvio.

## Referências

- [Configuração SMTP do Zoho Mail](https://www.zoho.com/mail/help/zoho-smtp.html)
- [Senhas específicas de aplicativo no Zoho](https://www.zoho.com/accounts/help/security/app-specific-passwords.html)
- [PHPMailer](https://github.com/PHPMailer/PHPMailer)
- [phpdotenv](https://github.com/phpdotenv/phpdotenv)
