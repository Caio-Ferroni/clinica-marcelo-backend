# Notificações toast

O sistema usa Toastify.js 1.12.0 nas páginas PHP, com arquivos locais e um catálogo compartilhado de 55 mensagens. As mensagens confirmam o resultado real das ações. O cadastro e a edição de pacientes, a atualização de tarefas e o relatório de consultas confirmam o sucesso somente após a gravação.

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

## Status automático de consultas

Na lista, o select salva ao mudar, sem recarregar ou reiniciar a paginação. O script `js/consultas.js` envia `POST /consultas/{id}/status` com `Accept: application/json`, `status` e `csrf_token` no corpo do formulário. As gravações são processadas em sequência; cada select fica indisponível durante seu envio. O lápis e os contadores só refletem o resultado confirmado pelo servidor.

O sucesso retorna HTTP 200 com `ok`, `toast_code`, `id`, `status`, `pode_editar` e `indicadores` (`mes`, `realizadas`, `canceladas`). Falhas retornam `ok: false` e `toast_code`: HTTP 401 para sessão expirada, 403 para token inválido, 404 para consulta inexistente, 422 para status inválido e 500 para falha ao gravar. As respostas JSON não deixam confirmação na fila da sessão. Sem JavaScript, o botão de envio continua disponível e usa o redirecionamento e a fila existentes.

Em falha de rede, resposta inválida ou erro do servidor, o select volta ao último valor confirmado e o aviso permanece disponível mesmo se o Toastify não carregar. O botão de visualizar usa somente o ícone de olho e abre os detalhes com ou sem relatório. O lápis só é liberado com status `Concluído`; pendente ou cancelado bloqueiam novamente a edição e preservam o texto salvo.

A abertura e o salvamento do relatório verificam o status no servidor; a atualização no banco também exige `Concluído`. Consultas fora desse estado retornam HTTP 409 e `report.not_completed`, inclusive se o formulário foi aberto antes da mudança de status. A distribuição deve incluir `js/consultas.js` junto das views e do backend alterados.

## Geração mensal de consultas

O botão “Gerar consultas do mês” e o salvamento do agendamento do paciente compartilham a recorrência contínua a partir da data inicial e criam somente horários futuros do mês atual. A geração geral cria os horários livres e apresenta um resumo dos conflitos. No paciente, um conflito bloqueia todo o salvamento, preserva os dados enviados e informa o paciente que ocupa o horário.

A recorrência mensal mantém a posição do dia da semana, como segunda quinta-feira, e usa a última ocorrência quando uma quinta não existir. Consultas antigas são preservadas. Horários consecutivos são permitidos, cancelamentos liberam o intervalo e a reativação passa novamente pela verificação. Regras, resultado interno e proteção de concorrência estão em [agenda-consultas.md](agenda-consultas.md).

O cadastro ou edição confirma o paciente e apresenta `patient-consultation.generated` quando criou consultas. A geração geral sem pendências mantém `consultation.generated` ou `consultation.none_generated`. Resultados parciais usam `consultation.generated_partial`; conflitos ou agendamentos inválidos sem novas consultas usam `consultation.generation_blocked`. Falha de gravação usa `consultation.generate_failed`, conflito usa `schedule.conflict` e timeout da trava usa `schedule.busy`.

O método JavaScript `ClinicToast.show(code, source, message)` aceita um terceiro argumento opcional para o texto contextual retornado pelo servidor, mantendo o tipo do catálogo e o escape de texto. O resumo do lote permanece visível na página após o toast.

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
| `account.load_failed` | Erro | Não foi possível acessar o cadastro. Verifique a conexão com o banco de dados. |
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
| `patient-consultation.generated` | Sucesso | As consultas do mês para este paciente foram geradas. |
| `consultation.none_generated` | Informação | Nenhuma nova consulta foi gerada. |
| `consultation.generate_failed` | Erro | Não foi possível gerar as consultas. Tente novamente. |
| `consultation.generated_partial` | Atenção | Algumas consultas foram geradas. Revise os horários ocupados e os agendamentos indicados. |
| `consultation.generation_blocked` | Atenção | Nenhuma nova consulta foi gerada. Revise os horários ocupados e os agendamentos indicados. |
| `schedule.conflict` | Atenção | Já existe uma consulta nesse horário. Escolha outro horário. |
| `schedule.busy` | Atenção | A agenda está sendo atualizada. Tente novamente em instantes. |
| `report.load_failed` | Erro | Não foi possível carregar o relatório. Tente novamente. |
| `patient.created` | Sucesso | Paciente cadastrado com sucesso. |
| `patient.updated` | Sucesso | Dados do paciente atualizados. |
| `consultation.rescheduled` | Sucesso | Os horários das consultas pendentes foram atualizados. |
| `patient.missing` | Erro | Paciente não encontrado. |
| `patient.save_failed` | Erro | Não foi possível salvar os dados do paciente. Tente novamente. |
| `patient.date_invalid` | Atenção | Informe uma data de nascimento válida. |
| `patient.optional_required` | Atenção | Preencha todos os campos da seção que deseja salvar. |
| `patient.schedule_invalid` | Atenção | Confira o dia, a frequência, a data e os horários do agendamento. |
| `schedule.search_invalid` | Atenção | Informe o dia, a repetição, a data inicial, a duração e uma faixa de busca válida. |
| `schedule.suggestions_failed` | Erro | Não foi possível consultar os horários livres. Tente novamente. |
| `schedule.selected` | Informação | Horário preenchido. Salve o paciente para confirmar o agendamento. |
| `validation.form_expired` | Atenção | O formulário expirou. Atualize a página e tente novamente. |
| `task.updated` | Sucesso | Tarefa atualizada com sucesso. |
| `task.missing` | Erro | Tarefa não encontrada. |
| `task.status_invalid` | Atenção | Selecione um status válido para a tarefa. |
| `report.saved` | Sucesso | Relatório da consulta salvo. |
| `report.not_completed` | Atenção | Conclua a consulta para editar o relatório. |
| `patient.load_failed` | Erro | Não foi possível carregar os pacientes. Tente novamente. |
| `patient.required` | Atenção | Preencha o nome, CPF, telefone e a data de nascimento do paciente. |
| `operation.failed` | Erro | Não foi possível concluir a ação. Tente novamente. |

## Validação local

Após a integração, passaram 134 verificações HTTP com SQLite isolado, 50 de regressão de consultas, 96 verificações do catálogo e da fila PHP, 31 verificações das máscaras, 14 verificações do dashboard e os 19 cenários de autenticação/SMTP. Também passaram 27 verificações dos models em tabelas temporárias com a estrutura real do MySQL, sem alterar os registros existentes. Nenhum e-mail real foi enviado.

No navegador foram conferidos o cadastro e a edição de paciente com máscaras e dados persistidos, a criação e a edição de tarefas, a atualização do relatório, as confirmações após redirecionamento e o dashboard. As verificações anteriores de teclado, fila, duplicações e alternativa sem biblioteca estão registradas no histórico da entrega de toasts. Os artefatos de validação ficam em `.local`, ignorado pelo Git. A publicação no servidor não foi realizada.

Para os ajustes de consultas, passaram 135 verificações HTTP, 50 de regressão de consultas, 12 do cliente (fila, falha de rede e alternativa sem Toastify), 98 do catálogo e da fila PHP e 35 em tabelas temporárias MySQL. No navegador foram conferidos os ícones, a manutenção da segunda página após o salvamento, a atualização dos contadores, os toasts, a liberação e o bloqueio do lápis, a falha de gravação, a sessão expirada e o envio de formulário aberto antes da mudança de status.

Para a correção do registro, passaram 51 verificações HTTP, 11 em tabela temporária MySQL com modo estrito, os 19 cenários de autenticação e 100 do catálogo e da fila PHP. A página real `/registrar` passou de HTTP 503 para HTTP 200 sem toast de erro inicial após corrigir as extensões do PHP local. Detalhes em [configuracao-local.md](configuracao-local.md).

Para a geração mensal, passaram 88 verificações HTTP, 21 em tabelas temporárias MySQL com modo estrito, 12 de regressão do cliente e 102 do catálogo e da fila PHP. Também passaram as 135 verificações de regressão de status e edição de relatório. No navegador foram conferidos cadastro e edição com geração automática, geração manual e repetição sem novas ocorrências. Os testes não alteraram pacientes nem consultas reais.

Para recorrência contínua e bloqueio de sobreposição, passaram 276 verificações de datas, 22 de geração e conflitos no MariaDB isolado, 69 HTTP, 6 de concorrência com duas conexões, 12 do cliente e 112 do catálogo/fila. Cadastro bloqueado com nome do paciente e geração parcial também foram conferidos no navegador.

Na busca de horários, as falhas de validação e de consulta usam os novos códigos de atenção e erro; selecionar uma sugestão usa `schedule.selected`, sem afirmar que o agendamento foi salvo. Um envio repetido já concluído não enfileira avisos de conflito nem novas confirmações. O conflito mantém uma orientação visível no formulário além do toast.
