# Recorrência e conflitos de agenda

## Datas e horários

A primeira ocorrência é o primeiro dia da semana selecionado que cai na data inicial do agendamento ou depois dela. A frequência semanal avança 7 dias e a quinzenal avança 14 dias a partir dessa ocorrência, sem reiniciar no começo de outro mês ou ano.

Exemplo: início em 01/10/2026, quinta-feira, quinzenal → 01/10, 15/10, 29/10, 12/11 e 26/11. Uma geração feita em 21/11 cria somente 26/11. Se a data inicial cair em uma quarta-feira e o dia escolhido for quinta-feira, a origem será a quinta imediatamente seguinte.

Na frequência mensal, a origem define a posição do dia da semana: segunda quinta-feira, terceira terça-feira etc. Uma quinta ocorrência ausente usa a última daquele dia da semana no mês. A posição original é mantida: o uso da quarta quinta-feira em fevereiro não transforma os meses seguintes em quarta ocorrência.

Os fluxos manual e automático geram somente ocorrências do mês atual cujo início seja posterior ao momento da geração, no fuso `America/Sao_Paulo`. Horários de hoje que já passaram são ignorados. A geração não atualiza, exclui nem cancela consultas existentes, inclusive as criadas anteriormente em datas incorretas.

## Sobreposição

O intervalo novo conflita quando `inicio_novo < fim_existente` e `fim_novo > inicio_existente` na mesma data. Isso bloqueia tanto horários iguais quanto sobreposições parciais, inclusive entre consultas distintas do mesmo paciente. Horários consecutivos são permitidos: 09:00–10:00 e 10:00–11:00.

Consultas canceladas liberam o horário para outro agendamento. Uma ocorrência já existente do mesmo agendamento, data e início é ignorada, mesmo cancelada, para não recriar um cancelamento. Reativar uma consulta cancelada verifica a disponibilidade novamente.

- Cadastro ou edição: um conflito retorna HTTP 409, reverte paciente, associações e novas consultas e reapresenta os dados preenchidos. O aviso identifica data, intervalo e paciente que ocupa o horário. Quando o vínculo do paciente está ausente, identifica a consulta pelo número.
- Geração geral: cria somente horários livres. Após o redirecionamento, mostra quantidades criadas e existentes, conflitos com os dois pacientes e seus horários, e agendamentos que precisam ser corrigidos. O resumo é consumido uma vez.
- Reativação: retorna HTTP 409 com `schedule.conflict`, `mensagem` e `conflito` quando o horário está ocupado. O select permanece no status anterior e mostra o aviso recebido como texto.

A verificação considera as ocorrências concretas da geração do mês e as consultas já gravadas. Agendamentos quinzenais em semanas alternadas podem compartilhar o mesmo horário. Não há reserva antecipada de todos os meses futuros: a disponibilidade é verificada a cada geração.

## Gravação e concorrência

`Backend/Support/Agenda.php` centraliza transação e exclusão por banco. No MySQL/MariaDB, usa `GET_LOCK` com nome específico da aplicação e do banco e espera máxima de 5 segundos. A trava é adquirida antes da transação e liberada em `finally` somente depois de `commit` ou `rollback`. Chamadas internas reutilizam o contexto da mesma conexão, sem abrir outra transação ou adquirir a trava recursivamente.

Cadastro, edição, geração geral e alteração de status usam essa mesma proteção. Assim, pacientes diferentes e solicitações de abas diferentes não validam e ocupam o mesmo intervalo simultaneamente. Timeout retorna HTTP 503 com `schedule.busy`; indisponibilidade não habilita gravação sem proteção. Não é necessária alteração das tabelas existentes.

`Consulta::gerarConsultas(?int $idPaciente = null, ?DateTimeImmutable $agora = null): array` retorna `criadas`, `existentes`, `conflitos` e `agendamentos_invalidos`. A consulta do paciente lança `ConflitoAgendaException` ao encontrar sobreposição; a geração geral reúne os conflitos e continua. O segundo argumento permite testes determinísticos. A função pura `RecorrenciaConsulta::datas` recebe data inicial, dia da semana, repetição e janela de datas. `Consulta::encontrarDatas` delega a esse cálculo e agora recebe esses mesmos argumentos.

Os toasts mantêm a confirmação específica `patient-consultation.generated` no paciente e usam mensagens de atenção para conflito, geração parcial ou agenda ocupada. Dados variáveis são escapados no HTML e apresentados como texto pelo Toastify; não são inseridos no catálogo global nem registrados nos logs.

## Validação de 21/11/2026

Passaram 276 verificações do cálculo puro, 22 de geração/sobreposição no MariaDB isolado, 69 HTTP dos formulários e respostas, 6 com duas conexões MariaDB simultâneas, 12 de regressão do cliente e 112 do catálogo e da fila de toasts. A disputa simultânea permitiu uma gravação e bloqueou a outra; também foram verificados timeout e liberação da trava após falha.

No navegador foram conferidos a ocorrência quinzenal correta, bloqueio com nome do paciente e formulário preservado, salvamento em horário consecutivo e geração parcial com resumo visível. Como o banco principal estava indisponível, foi usada uma instância MariaDB separada, na porta local 18770 e com arquivos em `.local`. Nenhum registro real foi alterado. Os artefatos de validação permanecem ignorados pelo Git.
