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

        public function criarAgendamento($id_paciente, $dia_semana, $repeticao, $horario_incio, $horario_termino, $data_inicio, $data_termino){
            $sql = "INSERT INTO agendamento(id_paciente, dia_semana, repeticao, horario_inicio, horario_termino, data_inicio, data_termino) VALUES (:id_paciente, :dia_semana, :repeticao, :horario_inicio, :horario_termino, :data_inicio, :data_termino)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':dia_semana' => $dia_semana,
                ':repeticao' => $repeticao,
                ':horario_inicio' => $horario_incio,
                ':horario_termino' => $horario_termino,
                ':data_inicio' => $data_inicio,
                ':data_termino' => $data_termino
            ]);
            $mensagem = "angendamento criado com sucesso";
            return $mensagem;
        }
        public function atualizarAgendamento($id_agendamento, $dia_semana, $repeticao, $horario_incio, $horario_termino, $data_inicio, $data_termino){
            
        }  
        public function visualizarAgendamento($id){
            $sql = "SELECT * FROM agendamento WHERE id_agedamento = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }
    }
?>