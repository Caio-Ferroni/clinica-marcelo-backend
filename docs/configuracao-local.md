# PHP local e cadastro da conta

O registro depende de PDO MySQL para consultar e gravar a conta e de mbstring para validar a senha. OpenSSL e curl também são necessários para os recursos de e-mail e notificações. O PHP do terminal pode usar uma configuração diferente da instalação Apache/XAMPP.

## Conferir o PHP usado pelo servidor

```powershell
php --ini
php -r 'echo implode(",", PDO::getAvailableDrivers()) . PHP_EOL; foreach (["mbstring", "openssl", "curl"] as $extension) echo $extension . "=" . (extension_loaded($extension) ? "OK" : "ausente") . PHP_EOL;'
```

O resultado deve incluir o driver `mysql` e as três extensões como `OK`. No `php.ini` indicado pelo primeiro comando, habilite:

```ini
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=curl
date.timezone=America/Sao_Paulo
```

No Windows, `extension_dir` deve apontar para a pasta `ext` da instalação usada. Na instalação local em `C:\php`, o caminho é `extension_dir="C:/php/ext"`. Depois de alterar a configuração, reinicie o processo que atende o site. Na raiz do projeto, o servidor de desenvolvimento pode ser iniciado com:

```powershell
php -S localhost:8000
```

## Registro e mensagens

Abra `http://localhost:8000/registrar`. Sem conta cadastrada e com o banco acessível, a página deve abrir com HTTP 200 e sem toast de erro. A conexão existente é definida em `Backend/Config/Database.php`; o banco precisa conter a tabela `psicologo`.

O formulário coleta e-mail, senha e confirmação. Como a tabela existente exige `telefone` sem valor padrão, o model grava esse campo vazio enquanto o cadastro não o coleta. Nenhum telefone fictício é criado e nenhuma alteração de esquema é necessária. A senha continua sendo salva com hash. A regra de uma única conta permanece: se já existir uma, `/registrar` redireciona para `/login` com a mensagem correspondente.

Falhas ao consultar a conta retornam HTTP 503 e “Não foi possível acessar o cadastro. Verifique a conexão com o banco de dados.” Falhas de gravação retornam HTTP 500, sem confirmação de sucesso. O log registra a etapa e o SQLSTATE, sem senha, e-mail ou detalhes da conexão. A configuração do envio de 2FA está em [configuracao-email.md](configuracao-email.md).

## Validação de 20/11/2026

Foi reproduzido HTTP 503 em `localhost:8000/registrar` com o PHP padrão sem driver PDO. Após habilitar as extensões e reiniciar esse servidor, a página real retornou HTTP 200, com formulário e sem erro inicial, também conferida no navegador.

Passaram 51 verificações HTTP isoladas de registro, validação, conta única, login e toasts; 11 verificações MySQL em tabela temporária com a estrutura real e modo estrito; 19 cenários anteriores de autenticação/SMTP simulado e 100 verificações do catálogo e da fila PHP. As contas reais foram preservadas e nenhum e-mail foi enviado. Os artefatos locais permanecem ignorados pelo Git. Nenhuma conta real foi criada durante a correção.
