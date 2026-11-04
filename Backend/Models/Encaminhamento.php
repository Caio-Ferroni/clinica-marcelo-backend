<?php
    class Encaminhamento{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM encaminhamento;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function criarEncaminhamento($id_paciente, $historico_paciente, $tipo_encaminhamento){
            $sql = "INSERT INTO encaminhamento(id_paciente, historico_paciente, tipo_encaminhamento) VALUES (:id_paciente, :historico_paciente, :tipo_encaminhamento)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':historico_paciente' => $historico_paciente,
                ':tipo_encaminhamento' => $tipo_encaminhamento
            ]);
            $mensagem = "encaminhamento criado com sucesso";
            return $mensagem;
        }

        public function atualizarEncaminhamento($id, $historico_paciente, $tipo_encaminhamento){

        }

        public function visualizarEncaminhamento($id){
            $sql = "SELECT * FROM encaminhamento WHERE id_encaminhamento = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }
    }
?>