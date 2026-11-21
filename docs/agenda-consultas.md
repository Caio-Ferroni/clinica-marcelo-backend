# Recorrência e conflitos de agenda

## Datas e horários

A primeira ocorrência é o primeiro dia da semana selecionado que cai na data inicial do agendamento ou depois dela. A frequência semanal avança 7 dias e a quinzenal avança 14 dias a partir dessa ocorrência, sem reiniciar no começo de outro mês ou ano.

Exemplo: início em 01/10/2026, quinta-feira, quinzenal → 01/10, 15/10, 29/10, 12/11 e 26/11. Uma geração feita em 21/11 cria somente 26/11. Se a data inicial cair em uma quarta-feira e o dia escolhido for quinta-feira, a origem será a quinta imediatamente seguinte.

Na frequência mensal, a origem define a posição do dia da semana: segunda quinta-feira, terceira terça-feira etc. Uma quinta ocorrência ausente usa a última daquele dia da semana no mês. A posição original é mantida: o uso da quarta quinta-feira em fevereiro não transforma os meses seguintes em quarta ocorrência.

Os fluxos manual e automático geram somente ocorrências de pacientes com status `Ativo`, no mês atual e cujo início seja posterior ao momento da geração, no fuso `America/Sao_Paulo`. Horários de hoje que já passaram são ignorados. O filtro também se aplica à geração de um único paciente. A geração não atualiza, exclui nem cancela consultas existentes, inclusive as criadas anteriormente em datas incorretas.

Pacientes inativos podem ter seus dados e seu agendamento editados sem gerar consultas nem exigir disponibilidade para o agendamento previsto. Ao voltar para `Ativo`, a edição verifica conflitos e gera as ocorrências futuras do mês. Se a reativação conflitar, a transação mantém o paciente inativo e preserva os dados anteriores. Agendamentos previstos de pacientes inativos não reservam horários nas sugestões; consultas já registradas continuam ocupando seus horários até serem canceladas explicitamente.

A página `/pacientes` exibe uma única tabela com as abas `Ativos`, `Inativos` e `Todos`, cada uma com sua contagem. A troca filtra os registros sem recarregar a página e volta à primeira página dos resultados, mantendo os ícones de edição e visualização. A aba escolhida também pode ser aberta pelo parâmetro `status` da URL; a opção inicial é `Ativo`. As setas, Home e End permitem navegar pelas abas usando o teclado. O total de pacientes inclui ambos os status. A seleção de paciente na edição também inclui inativos, permitindo consultar os dados e reativar o cadastro.

## Sobreposição

O intervalo novo conflita quando `inicio_novo < fim_existente` e `fim_novo > inicio_existente` na mesma data. Isso bloqueia tanto horários iguais quanto sobreposições parciais, inclusive entre consultas distintas do mesmo paciente. Horários consecutivos são permitidos: 09:00–10:00 e 10:00–11:00.

Consultas canceladas liberam o horário para outro agendamento. Uma ocorrência já existente do mesmo agendamento, data e início é ignorada, mesmo cancelada, para não recriar um cancelamento. Reativar uma consulta cancelada verifica a disponibilidade novamente.

- Cadastro ou edição: um conflito retorna HTTP 409, reverte paciente, associações e novas consultas e reapresenta os dados preenchidos. O aviso identifica data, intervalo e paciente que ocupa o horário. Quando o vínculo do paciente está ausente, identifica a consulta pelo número.
- Geração geral: cria somente horários livres. Após o redirecionamento, mostra quantidades criadas e existentes, conflitos com os dois pacientes e seus horários, e agendamentos que precisam ser corrigidos. O resumo é consumido uma vez.
- Reativação: retorna HTTP 409 com `schedule.conflict`, `mensagem` e `conflito` quando o horário está ocupado. O select permanece no status anterior e mostra o aviso recebido como texto.

A verificação no cadastro e na edição considera as consultas já gravadas e as ocorrências previstas dos outros agendamentos, mesmo quando suas consultas ainda não foram geradas. No cadastro, a pré-validação ocorre dentro da trava e antes de inserir o paciente. Na edição, a atualização das pendentes e a validação do agendamento compartilham a transação do paciente; qualquer conflito ou falha reverte todas as alterações. Agendamentos quinzenais em semanas alternadas podem compartilhar o mesmo horário. Não há reserva antecipada de todos os meses futuros: a geração verifica as ocorrências do mês atual, e a disponibilidade é conferida novamente a cada geração.

## Alteração dos horários do paciente

Ao editar um agendamento existente e mudar o início ou o término, o sistema atualiza esses dois campos somente nas consultas com status `Pendente` do paciente. Mantém IDs, datas, status e relatórios. A regra inclui pendentes antigas ou de outros meses e também pendentes já existentes de pacientes inativos; inativos continuam sem gerar novas consultas. Alterar apenas dados pessoais, sem mudar os horários do agendamento, não reprograma as consultas.

As novas faixas são verificadas em todas as datas das pendentes, incluindo consultas e agendamentos previstos de outros pacientes. Os horários antigos das próprias pendentes em atualização não bloqueiam a mudança. Conflitos entre as próprias consultas resultantes também são bloqueados. Um conflito retorna HTTP 409 e desfaz a edição do paciente, do agendamento e das consultas; uma falha SQL tem a mesma reversão.

Consultas `Concluído` e `Cancelado` não são alteradas. A geração mensal reconhece essas ocorrências pela combinação de agendamento e data, mesmo se seus horários forem diferentes do novo agendamento, para não criar substitutas. As sugestões na edição desconsideram os horários antigos das pendentes e as ocorrências encerradas que não serão geradas novamente. O salvamento sempre verifica todas as pendentes antes de confirmar.

Quando houve atualização de pendentes, a confirmação inclui `consultation.rescheduled`: “Os horários das consultas pendentes foram atualizados.” Passaram 42 verificações HTTP específicas de mudança de início e término, preservação de histórico, ausência de duplicatas, pendentes de outros meses, edição de inativo, conflito e rollback por falha SQL, em banco isolado. Também passaram 22 de geração, 76 HTTP de regressão e 120 do catálogo e fila de toasts.

## Reenvio do formulário

Cada formulário de cadastro e edição inclui `envio_token`, associado à sessão, à ação e ao paciente na edição. O token dura duas horas e só é concluído após a confirmação da transação. Repetir um envio já concluído retorna HTTP 303 para o paciente salvo, sem gravar novamente ou acusar conflito com o próprio cadastro. Um erro mantém o token reutilizável para corrigir o horário e tentar de novo. Tokens ausentes, expirados ou de outro formulário retornam HTTP 403, assim como falhas de CSRF. Formulários abertos antes dessa atualização precisam ser recarregados.

O botão fica desabilitado durante o envio e é restaurado ao voltar pelo navegador. Essa proteção visual complementa a proteção no servidor. Um novo formulário continua sujeito à validação normal de conflitos; pacientes diferentes com o mesmo nome não são ignorados.

## Sugestões de horários

Cadastro e edição oferecem uma faixa de busca ajustável, inicialmente 08:00–20:00. Essa faixa é apenas o filtro inicial da busca, sem impor um expediente fixo à clínica. A duração acompanha o intervalo informado ou começa em 50 minutos quando ainda não há intervalo, podendo ser alterada. Domingo não recebe sugestões.

`POST /pacientes/horarios-livres` exige autenticação e CSRF e não grava dados. Recebe `dia_semana`, `repeticao`, `data_inicio`, `duracao`, `busca_inicio`, `busca_fim` e, opcionalmente, os horários atuais e `id_paciente` na edição. O servidor busca o agendamento pelo paciente, sem aceitar um agendamento arbitrário para excluir da verificação.

São retornadas até seis opções próximas do início informado, preservando a duração. A busca usa passos de 15 minutos e também os términos das ocupações, para encontrar encaixes fora dessa grade em faixas apertadas. Cada opção está livre em todas as datas futuras da repetição no período consultado. O resultado informa o período e as datas verificadas por opção. Sem ocorrências futuras neste mês, a busca avança para o próximo período da repetição ou para o mês da data inicial futura. A busca também considera agendamentos ainda não gerados. Ocorrências já registradas, inclusive canceladas, não voltam a ocupar a agenda como reservas previstas.

Na edição, a mesma ocorrência já gerada não conflita consigo; uma consulta distinta do mesmo paciente continua bloqueando sobreposição. Escolher uma opção preenche início e término e mostra `schedule.selected`; salvar continua sendo uma ação explícita. Nenhuma busca reserva o horário. Se outra solicitação ocupar o intervalo depois da busca, a pré-validação do salvamento bloqueia a gravação.

Conflitos reapresentam os campos, mantêm uma mensagem visível junto à busca e carregam sugestões automaticamente. Alterações nos campos descartam resultados antigos e respostas atrasadas. Falhas de busca mostram orientação e toast, permitindo tentar novamente.

## Gravação e concorrência

`Backend/Support/Agenda.php` centraliza transação e exclusão por banco. No MySQL/MariaDB, usa `GET_LOCK` com nome específico da aplicação e do banco e espera máxima de 5 segundos. A trava é adquirida antes da transação e liberada em `finally` somente depois de `commit` ou `rollback`. Chamadas internas reutilizam o contexto da mesma conexão, sem abrir outra transação ou adquirir a trava recursivamente.

Cadastro, edição, geração geral e alteração de status usam essa mesma proteção. Assim, pacientes diferentes e solicitações de abas diferentes não validam e ocupam o mesmo intervalo simultaneamente. Timeout retorna HTTP 503 com `schedule.busy`; indisponibilidade não habilita gravação sem proteção. Não é necessária alteração das tabelas existentes.

`Consulta::gerarConsultas(?int $idPaciente = null, ?DateTimeImmutable $agora = null): array` retorna `criadas`, `existentes`, `conflitos` e `agendamentos_invalidos`. A consulta do paciente lança `ConflitoAgendaException` ao encontrar sobreposição; a geração geral reúne os conflitos e continua. O segundo argumento permite testes determinísticos. A função pura `RecorrenciaConsulta::datas` recebe data inicial, dia da semana, repetição e janela de datas. `Consulta::encontrarDatas` delega a esse cálculo e agora recebe esses mesmos argumentos.

Os toasts mantêm a confirmação específica `patient-consultation.generated` no paciente e usam mensagens de atenção para conflito, geração parcial ou agenda ocupada. Dados variáveis são escapados no HTML e apresentados como texto pelo Toastify; não são inseridos no catálogo global nem registrados nos logs.

## Validação de 21/11/2026

Passaram 276 verificações do cálculo puro, 22 de geração/sobreposição no MariaDB isolado, 69 HTTP dos formulários e respostas, 6 com duas conexões MariaDB simultâneas, 12 de regressão do cliente e 112 do catálogo e da fila de toasts. A disputa simultânea permitiu uma gravação e bloqueou a outra; também foram verificados timeout e liberação da trava após falha.

No navegador foram conferidos a ocorrência quinzenal correta, bloqueio com nome do paciente e formulário preservado, salvamento em horário consecutivo e geração parcial com resumo visível. Como o banco principal estava indisponível, foi usada uma instância MariaDB separada, na porta local 18770 e com arquivos em `.local`. Nenhum registro real foi alterado. Os artefatos de validação permanecem ignorados pelo Git.

Na correção de reenvios e nas sugestões, foi reproduzido o comportamento: primeiro envio HTTP 303, segundo envio do mesmo formulário HTTP 409 citando o próprio paciente, com os dados da primeira operação já salvos. Com o token de envio, a repetição retorna o mesmo cadastro e não altera nenhuma tabela. O banco principal foi inspecionado apenas para confirmar a estrutura InnoDB e a ausência de gatilhos; os testes de escrita continuaram no MariaDB separado.

As verificações adicionais cobrem reenvio de criação e edição, novas tentativas após conflito, falha SQL, preservação dos campos, consulta sem gravação, agendamentos previstos, todas as datas da repetição, cancelamento, horários consecutivos, busca futura, domingo, acesso não autenticado, CSRF e respostas atrasadas. No navegador foram conferidos cadastro por sugestão, conflito com orientação e opções automáticas, recuperação em horário livre com toasts de sucesso e sugestões na edição.
