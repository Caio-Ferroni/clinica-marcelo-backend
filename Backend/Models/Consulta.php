<?php
require_once __DIR__ . '/../Config/Database.php';
    class Consulta{
        public const STATUS = ['Pendente', 'Concluído', 'Cancelado'];

        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection(true);
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

        public function gerarConsultas(?int $idPaciente = null): int {
            $agora = new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
            $transacaoPropria = !$this->db->inTransaction();
            if ($transacaoPropria) $this->db->beginTransaction();
            try {
                $sql = 'SELECT * FROM agendamento';
                if ($idPaciente !== null) $sql .= ' WHERE id_paciente = :id_paciente';
                $sql .= ' ORDER BY id_agendamento';
                if ($this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') $sql .= ' FOR UPDATE';
                $stmt = $this->db->prepare($sql);
                $stmt->execute($idPaciente === null ? [] : [':id_paciente' => $idPaciente]);
                $agendamentos = $stmt->fetchAll();
                $consultasCriadas = 0;
                foreach ($agendamentos as $agendamento) {
                    if (!in_array((string) $agendamento['dia_semana'], ['0', '1', '2', '3', '4', '5', '6'], true)
                        || !in_array($agendamento['repeticao'], ['Semanal', 'Quinzenal', 'Mensal'], true)
                        || empty($agendamento['horario_inicio']) || empty($agendamento['horario_termino'])) continue;
                    $datas = self::encontrarDatas($agora->format('Y'), $agora->format('m'), $agendamento['dia_semana'], $agendamento['repeticao']);
                    foreach ($datas as $data) {
                        if (self::consultaExiste($agendamento['id_agendamento'], $data, $agendamento['horario_inicio'])) continue;
                        $stmt = $this->db->prepare('INSERT INTO consulta (id_agendamento, horario_inicio, horario_fim, data, status) VALUES (:id_agendamento, :horario_inicio, :horario_fim, :data, :status)');
                        $stmt->execute([
                            ':id_agendamento' => $agendamento['id_agendamento'],
                            ':horario_inicio' => $agendamento['horario_inicio'],
                            ':horario_fim' => $agendamento['horario_termino'],
                            ':data' => $data,
                            ':status' => 'Pendente'
                        ]);
                        $consultasCriadas++;
                    }
                }
                if ($transacaoPropria) $this->db->commit();
                return $consultasCriadas;
            } catch (Throwable $e) {
                if ($transacaoPropria && $this->db->inTransaction()) $this->db->rollBack();
                throw $e;
            }
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

            $sql = "UPDATE consulta SET status = :status WHERE id_consulta = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);

            return $stmt->rowCount() > 0 || $this->visualizarConsulta($id) !== [];
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

        public static function encontrarDatas($ano, $mes, $diaAlvo, $repeticao){
            $datas = [];
            $diaAlvo = (string) $diaAlvo;
            $primeiroDia = new DateTime("$ano-$mes-01");
            $ultimoDia = (clone $primeiroDia)->modify('last day of this month');
            $cursor = (clone $primeiroDia);
            while ($cursor->format('w') !== $diaAlvo){
                $cursor->modify('+1 day');
            }

            $diaSemanalCursor = $cursor->format('w');
            while($cursor <= $ultimoDia){


                if($repeticao === 'Semanal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+1 week');
                }

                if($repeticao === 'Quinzenal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+2 weeks');
                }

                if($repeticao === 'Mensal' && $diaSemanalCursor === $diaAlvo){
                    $diaMesAno = (array) $cursor;
                    array_push($datas, substr($diaMesAno['date'], 0, 10));
                    $cursor->modify('+1 month');
                }

            }



            return $datas;

        }

        private static function consultaExiste($id_agendamento, $data_consultada, $horario_inicio): bool {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM consulta
                WHERE id_agendamento = :id_agendamento AND data = :data AND horario_inicio = :horario_inicio
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
