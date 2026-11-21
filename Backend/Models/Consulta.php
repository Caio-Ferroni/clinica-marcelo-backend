<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Support/Agenda.php';
require_once __DIR__ . '/../Support/RecorrenciaConsulta.php';
require_once __DIR__ . '/../Support/DisponibilidadeAgenda.php';
    class Consulta{
        public const STATUS = ['Pendente', 'Concluído', 'Cancelado'];

        protected PDO $db;

        public function __construct(?PDO $db = null) {
            $this->db = $db ?? Database::getConnection(true);
        }

        public function listarTodos(){
            $sql = 'SELECT
                        c.*,
                        a.id_paciente,
                        p.nome AS paciente_nome,
                        p.data_nascimento,
                        p.telefone,
                        p.cpf
                    FROM consulta c
                    LEFT JOIN agendamento a ON c.id_agendamento = a.id_agendamento
                    LEFT JOIN paciente p ON a.id_paciente = p.id_paciente
                    ORDER BY c.data DESC, c.horario_inicio ASC, c.id_consulta DESC;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function gerarConsultas(?int $idPaciente = null, ?DateTimeImmutable $agora = null): array {
            return AgendaTransacao::executar($this->db, function () use ($idPaciente, $agora) {
                $agora = ($agora ?? new DateTimeImmutable('now'))->setTimezone(new DateTimeZone('America/Sao_Paulo'));
                $fim = $agora->modify('last day of this month')->setTime(23, 59, 59);
                $resultado = ['criadas' => 0, 'existentes' => 0, 'conflitos' => [], 'agendamentos_invalidos' => []];
                $sql = 'SELECT a.*, p.nome AS paciente_nome FROM agendamento a INNER JOIN paciente p ON p.id_paciente = a.id_paciente WHERE p.status = :status_paciente';
                $parametros = [':status_paciente' => 'Ativo'];
                if ($idPaciente !== null) {
                    $sql .= ' AND a.id_paciente = :id_paciente';
                    $parametros[':id_paciente'] = $idPaciente;
                }
                $sql .= ' ORDER BY a.id_agendamento';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($parametros);
                $agendamentos = $stmt->fetchAll();
                foreach ($agendamentos as $agendamento) {
                    try {
                        if (!in_array((string) $agendamento['dia_semana'], ['0', '1', '2', '3', '4', '5', '6'], true)
                            || !$this->horarioValido($agendamento['horario_inicio']) || !$this->horarioValido($agendamento['horario_termino'])
                            || substr($agendamento['horario_inicio'], 0, 5) >= substr($agendamento['horario_termino'], 0, 5)) {
                            throw new InvalidArgumentException('Agendamento inválido.');
                        }
                        $datas = self::encontrarDatas((string) $agendamento['data_inicio'], (int) $agendamento['dia_semana'], (string) $agendamento['repeticao'], $agora, $fim);
                    } catch (InvalidArgumentException $e) {
                        if ($idPaciente !== null) throw $e;
                        $resultado['agendamentos_invalidos'][] = ['id_agendamento' => (int) $agendamento['id_agendamento'], 'paciente_nome' => $agendamento['paciente_nome']];
                        continue;
                    }
                    foreach ($datas as $data) {
                        $inicio = new DateTimeImmutable($data . ' ' . $agendamento['horario_inicio'], $agora->getTimezone());
                        if ($inicio <= $agora) continue;
                        if ($this->consultaExiste($agendamento['id_agendamento'], $data, $agendamento['horario_inicio'])) {
                            $resultado['existentes']++;
                            continue;
                        }
                        $conflito = $this->encontrarConflito($data, $agendamento['horario_inicio'], $agendamento['horario_termino']);
                        if ($conflito !== null) {
                            if ($idPaciente !== null) throw new ConflitoAgendaException($conflito);
                            $resultado['conflitos'][] = $conflito + [
                                'paciente_solicitante' => $agendamento['paciente_nome'],
                                'inicio_solicitado' => $agendamento['horario_inicio'],
                                'fim_solicitado' => $agendamento['horario_termino']
                            ];
                            continue;
                        }
                        $stmt = $this->db->prepare('INSERT INTO consulta (id_agendamento, horario_inicio, horario_fim, data, status) VALUES (:id_agendamento, :horario_inicio, :horario_fim, :data, :status)');
                        $stmt->execute([
                            ':id_agendamento' => $agendamento['id_agendamento'],
                            ':horario_inicio' => $agendamento['horario_inicio'],
                            ':horario_fim' => $agendamento['horario_termino'],
                            ':data' => $data,
                            ':status' => 'Pendente'
                        ]);
                        $resultado['criadas']++;
                    }
                }
                return $resultado;
            });
        }

        public function validarAgendamento(array $agendamento, int $idAgendamento = 0): void {
            (new DisponibilidadeAgenda($this->db))->validar($agendamento, $idAgendamento);
        }

        public function atualizarHorariosPendentes(int $idPaciente, string $inicio, string $fim): int {
            return AgendaTransacao::executar($this->db, function () use ($idPaciente, $inicio, $fim) {
                $stmt = $this->db->prepare("SELECT c.*, a.id_paciente, p.nome AS paciente_nome FROM consulta c
                    INNER JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                    INNER JOIN paciente p ON p.id_paciente = a.id_paciente
                    WHERE a.id_paciente = ? AND c.status = 'Pendente' ORDER BY c.data, c.id_consulta");
                $stmt->execute([$idPaciente]);
                $consultas = $stmt->fetchAll();
                (new DisponibilidadeAgenda($this->db))->validarAlteracaoHorarios($consultas, $inicio, $fim);
                $stmt = $this->db->prepare("UPDATE consulta c INNER JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                    SET c.horario_inicio = ?, c.horario_fim = ? WHERE a.id_paciente = ? AND c.status = 'Pendente'");
                $stmt->execute([$inicio, $fim, $idPaciente]);
                return $stmt->rowCount();
            });
        }

        private function horarioValido($horario): bool {
            return is_string($horario) && preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?$/D', $horario) === 1;
        }

        private function encontrarConflito(string $data, string $inicio, string $fim, int $ignorar = 0): ?array {
            $stmt = $this->db->prepare("SELECT c.id_consulta, c.data, c.horario_inicio, c.horario_fim, a.id_paciente, p.nome AS paciente_nome
                FROM consulta c LEFT JOIN agendamento a ON a.id_agendamento = c.id_agendamento
                LEFT JOIN paciente p ON p.id_paciente = a.id_paciente
                WHERE c.data = :data AND COALESCE(c.status, 'Pendente') <> 'Cancelado'
                    AND c.horario_inicio < :fim AND c.horario_fim > :inicio AND c.id_consulta <> :ignorar
                ORDER BY c.horario_inicio, c.id_consulta LIMIT 1");
            $stmt->execute([':data' => $data, ':inicio' => $inicio, ':fim' => $fim, ':ignorar' => $ignorar]);
            return $stmt->fetch() ?: null;
        }

        public function visualizarCalendario(){

        }

        public function visualizarConsulta($id){
            $sql = "SELECT
                        c.*,
                        a.id_paciente,
                        p.nome AS paciente_nome,
                        p.data_nascimento,
                        p.telefone,
                        p.cpf
                    FROM consulta c
                    LEFT JOIN agendamento a ON c.id_agendamento = a.id_agendamento
                    LEFT JOIN paciente p ON a.id_paciente = p.id_paciente
                    WHERE c.id_consulta = :id
                    LIMIT 1;";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function atualizarStatus($id, $status): bool {
            if (!is_string($status) || !in_array($status, self::STATUS, true)) {
                throw new InvalidArgumentException('Status de consulta inválido.');
            }

            return AgendaTransacao::executar($this->db, function () use ($id, $status) {
                $consulta = $this->visualizarConsulta($id)[0] ?? null;
                if ($consulta === null) return false;
                if ($consulta['status'] === 'Cancelado' && $status !== 'Cancelado') {
                    $conflito = $this->encontrarConflito($consulta['data'], $consulta['horario_inicio'], $consulta['horario_fim'], (int) $id);
                    if ($conflito !== null) throw new ConflitoAgendaException($conflito);
                }
                $stmt = $this->db->prepare('UPDATE consulta SET status = :status WHERE id_consulta = :id');
                $stmt->execute([':status' => $status, ':id' => $id]);
                return true;
            });
        }

        public function enviarNotificação(){

        }
        public static function podeEditarRelatorio(array $consulta): bool {
            return ($consulta['status'] ?? null) === 'Concluído';
        }

        public function gerarRelatorio($id, $relatorio): bool {
            $sql = "UPDATE consulta SET relatorio = :relatorio WHERE id_consulta = :id AND status = :status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':relatorio' => $relatorio,
                ':id' => $id,
                ':status' => 'Concluído'
            ]);
            if ($stmt->rowCount() > 0) return true;
            $consulta = $this->visualizarConsulta($id)[0] ?? [];
            return self::podeEditarRelatorio($consulta) && ($consulta['relatorio'] ?? null) === $relatorio;
        }

        public static function encontrarDatas(string $dataInicio, int $diaAlvo, string $repeticao, DateTimeImmutable $de, DateTimeImmutable $ate): array {
            return RecorrenciaConsulta::datas($dataInicio, $diaAlvo, $repeticao, $de, $ate);
        }

        private function consultaExiste($id_agendamento, $data_consultada, $horario_inicio): bool {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM consulta
                WHERE id_agendamento = :id_agendamento AND data = :data
                    AND (horario_inicio = :horario_inicio OR status IN ('Concluído', 'Cancelado'))
            ");
            $stmt->execute([
                ':id_agendamento' => $id_agendamento,
                ':data'      => $data_consultada,
                ':horario_inicio'      => $horario_inicio

            ]);

            return $stmt->fetchColumn() > 0;
        }
    }
?>
