# Notificações toast

O sistema usa Toastify.js 1.12.0 nas páginas PHP, com arquivos locais e um catálogo compartilhado de 43 mensagens. As mensagens confirmam o resultado real das ações. O cadastro e a edição de pacientes, a atualização de tarefas e o relatório de consultas confirmam o sucesso somente após a gravação.

## Comportamento

- Sucesso: 4 segundos. Informação: 5 segundos. Atenção: 6 segundos. Erros ficam visíveis até o fechamento.
- O temporizador pausa durante o foco de teclado ou a passagem do mouse. O botão Fechar notificação funciona pelo teclado e devolve o foco ao controle anterior quando disponível.
- Há até três toasts simultâneos. Os seguintes entram na fila e mensagens iguais não são duplicadas enquanto estiverem ativas. As notificações acompanham a camada dos modais.
- Os avisos fixos permanecem disponíveis se o JavaScript ou a biblioteca não carregar. Estados vazios, contagens e resultados de pesquisa continuam como conteúdo das páginas.
- Os textos são inseridos como texto, sem executar HTML. Senhas, códigos de verificação e dados de pacientes não fazem parte do catálogo.

## Integração

`Backend/Support/Toast.php` define o catálogo e a fila da sessão. `Views/Shared/toasts.php` publica o catálogo e renderiza a alternativa sem JavaScript. `js/toasts.js` controla a apresentação, a fila e a validação dos formulários marcados com `data-toast-validate`.

Uma confirmação após gravação usa:

```php
Toast::add('task.created');
header('Location: /tarefas', true, 303);
exit;
```

A página de destino consome a fila uma única vez. Avisos de atenção e erro eliminam confirmações de sucesso ainda pendentes. No logout, a sessão autenticada é encerrada e a confirmação usa uma nova sessão anônima.

Para uma ação no navegador:

```javascript
ClinicToast.show('validation.required');
```

O segundo argumento opcional aceita o elemento existente que serve de aviso alternativo. O serviço só oculta esse elemento depois de apresentar o toast. Códigos desconhecidos retornam `false`. Avisos PHP já renderizados usam `data-toast-type` e, opcionalmente, `data-toast-code`.

Login, 2FA, registro da conta, criação e edição de tarefas, cadastro e edição de pacientes, status e geração de consultas, leitura e edição de relatório estão conectados. O envio do código depende da aceitação do SMTP; o toast não comprova recebimento na caixa de entrada. Web Push e APIs JSON permanecem separados.

## Dependência e publicação

A dependência `toastify-js` tem versão exata. Para reinstalar e atualizar as cópias locais:

```powershell
npm.cmd install --ignore-scripts --no-audit --no-fund
npm.cmd run vendor:toastify
```

Publicar os arquivos versionados de `js/vendor`, `css/vendor`, `css/toasts.css`, `js/toasts.js`, o helper e o componente compartilhado junto das páginas e controllers alterados. A licença MIT está em `js/vendor/toastify.LICENSE.txt`. O script de sincronização remove comentários das cópias distribuídas e preserva a licença em arquivo separado. O navegador não precisa acessar um CDN para carregar o Toastify. Os recursos próprios usam a data de modificação na URL para atualizar o cache após alterações.

Os novos formulários de paciente, edição de tarefa e relatório usam token CSRF de sessão. Um formulário expirado retorna HTTP 403 e uma mensagem específica. Dados inválidos retornam HTTP 422. O cadastro e a atualização de paciente usam transação para evitar gravações parciais das associações.

Os relatórios ficam em `/consultas/{id}`, com edição em `/consultas/{id}/editar`. Os endereços antigos `/relatorios/{id}` continuam disponíveis; `/relatorios` redireciona para a lista de consultas.

## Catálogo

| Código | Tipo | Mensagem |
|---|---|---|
| `validation.required` | Atenção | Preencha os campos obrigatórios. |
| `validation.email` | Atenção | Informe um e-mail válido. |
| `validation.password_length` | Atenção | A senha deve ter pelo menos 8 caracteres. |
| `validation.password_match` | Atenção | As senhas não coincidem. |
| `validation.code` | Atenção | Informe um código de 6 dígitos. |
| `validation.fields` | Atenção | Confira os campos destacados e tente novamente. |
| `auth.credentials` | Erro | E-mail ou senha incorretos. |
| `auth.code_sent` | Informação | Código enviado. Confira seu e-mail para continuar. |
| `auth.code_invalid` | Erro | Código inválido ou expirado. Confira o código ou entre novamente. |
| `auth.code_failed` | Erro | Não foi possível enviar o código de verificação. Tente entrar novamente em instantes. |
| `auth.login` | Sucesso | Você entrou no sistema. |
| `auth.required` | Atenção | Entre na sua conta para continuar. |
| `auth.logout` | Informação | Você saiu do sistema. |
| `account.created` | Sucesso | Conta criada. Entre para continuar. |
| `account.exists` | Informação | Já existe uma conta cadastrada. Entre para continuar. |
| `account.failed` | Erro | Não foi possível realizar o cadastro. |
| `task.created` | Sucesso | Tarefa criada com sucesso. |
| `task.required` | Atenção | Preencha o título, o conteúdo e a data de conclusão da tarefa. |
| `task.date` | Atenção | Informe uma data de conclusão válida. |
| `consultation.status_saved` | Sucesso | Status da consulta atualizado. |
| `consultation.status_invalid` | Atenção | Selecione um status válido para a consulta. |
| `consultation.form_expired` | Atenção | O formulário expirou. Tente salvar o status novamente. |
| `consultation.missing` | Erro | Consulta não encontrada. |
| `consultation.load_failed` | Erro | Não foi possível carregar as consultas. Tente novamente. |
| `consultation.save_failed` | Erro | Não foi possível salvar o status. Tente novamente. |
| `consultation.generated` | Sucesso | As consultas do mês foram geradas. |
| `consultation.none_generated` | Informação | Nenhuma nova consulta foi gerada. |
| `report.load_failed` | Erro | Não foi possível carregar o relatório. Tente novamente. |
| `patient.created` | Sucesso | Paciente cadastrado com sucesso. |
| `patient.updated` | Sucesso | Dados do paciente atualizados. |
| `patient.missing` | Erro | Paciente não encontrado. |
| `patient.save_failed` | Erro | Não foi possível salvar os dados do paciente. Tente novamente. |
| `patient.date_invalid` | Atenção | Informe uma data de nascimento válida. |
| `patient.optional_required` | Atenção | Preencha todos os campos da seção que deseja salvar. |
| `patient.schedule_invalid` | Atenção | Confira o dia, a frequência, a data e os horários do agendamento. |
| `validation.form_expired` | Atenção | O formulário expirou. Atualize a página e tente novamente. |
| `task.updated` | Sucesso | Tarefa atualizada com sucesso. |
| `task.missing` | Erro | Tarefa não encontrada. |
| `task.status_invalid` | Atenção | Selecione um status válido para a tarefa. |
| `report.saved` | Sucesso | Relatório da consulta salvo. |
| `patient.load_failed` | Erro | Não foi possível carregar os pacientes. Tente novamente. |
| `patient.required` | Atenção | Preencha o nome, CPF, telefone e a data de nascimento do paciente. |
| `operation.failed` | Erro | Não foi possível concluir a ação. Tente novamente. |

## Validação local

Após a integração, passaram 134 verificações HTTP com SQLite isolado, 50 de regressão de consultas, 96 verificações do catálogo e da fila PHP, 31 verificações das máscaras, 14 verificações do dashboard e os 19 cenários de autenticação/SMTP. Também passaram 27 verificações dos models em tabelas temporárias com a estrutura real do MySQL, sem alterar os registros existentes. Nenhum e-mail real foi enviado.

No navegador foram conferidos o cadastro e a edição de paciente com máscaras e dados persistidos, a criação e a edição de tarefas, a atualização do relatório, as confirmações após redirecionamento e o dashboard. As verificações anteriores de teclado, fila, duplicações e alternativa sem biblioteca estão registradas no histórico da entrega de toasts. Os artefatos de validação ficam em `.local`, ignorado pelo Git. A publicação no servidor não foi realizada.
