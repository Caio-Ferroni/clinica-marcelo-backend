<?php
    class Agendamento{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM agendamento;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function buscarPorPaciente($id_paciente): array {
            $stmt = $this->db->prepare('SELECT * FROM agendamento WHERE id_paciente = :id_paciente LIMIT 1');
            $stmt->execute([':id_paciente' => $id_paciente]);
            return $stmt->fetch() ?: [];
        }

        public function criarAgendamento($id_paciente, $dia_semana, $repeticao, $horario_inicio, $horario_termino, $data_inicio){
            $sql = "INSERT INTO agendamento(id_paciente, dia_semana, repeticao, horario_inicio, horario_termino, data_inicio) VALUES (:id_paciente, :dia_semana, :repeticao, :horario_inicio, :horario_termino, :data_inicio)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente'    => $id_paciente,
                ':dia_semana'     => $dia_semana,
                ':repeticao'      => $repeticao,
                ':horario_inicio' => $horario_inicio,
                ':horario_termino'=> $horario_termino,
                ':data_inicio'    => $data_inicio
                
            ]);
            return "agendamento criado com sucesso";
        }

        // Cria ou atualiza o agendamento do paciente (upsert por id_paciente)
        public function atualizarAgendamento($id_paciente, $dia_semana, $repeticao, $horario_inicio, $horario_termino, $data_inicio){
            if(!self::agendamentoExiste($id_paciente)){
                return $this->criarAgendamento($id_paciente, $dia_semana, $repeticao, $horario_inicio, $horario_termino, $data_inicio);
            }

            $sql = "UPDATE agendamento
                    SET dia_semana = :dia_semana, repeticao = :repeticao,
                        horario_inicio = :horario_inicio, horario_termino = :horario_termino,
                        data_inicio = :data_inicio
                    WHERE id_paciente = :id_paciente";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':dia_semana'      => $dia_semana,
                ':repeticao'       => $repeticao,
                ':horario_inicio'  => $horario_inicio,
                ':horario_termino' => $horario_termino,
                ':data_inicio'     => $data_inicio,
                ':id_paciente'     => $id_paciente
            ]);
            return "Agendamento atualizado";
        }

        private static function agendamentoExiste($id_paciente): bool {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM agendamento WHERE id_paciente = :id_paciente");
            $stmt->execute([':id_paciente' => $id_paciente]);
            return $stmt->fetchColumn() > 0;
        }
        public function visualizarAgendamento($id){
            $sql = "SELECT * FROM agendamento WHERE id_agendamento = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }
    }
?>
