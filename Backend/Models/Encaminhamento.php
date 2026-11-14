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

        public function buscarPorPaciente($id_paciente): array {
            $stmt = $this->db->prepare('SELECT * FROM encaminhamento WHERE id_paciente = :id_paciente LIMIT 1');
            $stmt->execute([':id_paciente' => $id_paciente]);
            return $stmt->fetch() ?: [];
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

        // Cria ou atualiza o encaminhamento do paciente (upsert por id_paciente)
        public function atualizarEncaminhamento($id_paciente, $historico_paciente, $tipo_encaminhamento){
            if(!self::encaminhamentoExiste($id_paciente)){
                return $this->criarEncaminhamento($id_paciente, $historico_paciente, $tipo_encaminhamento);
            }

            $sql = "UPDATE encaminhamento
                    SET historico_paciente = :historico_paciente, tipo_encaminhamento = :tipo_encaminhamento
                    WHERE id_paciente = :id_paciente";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':historico_paciente'    => $historico_paciente,
                ':tipo_encaminhamento'   => $tipo_encaminhamento,
                ':id_paciente'           => $id_paciente
            ]);
            return "Encaminhamento atualizado";
        }

        private static function encaminhamentoExiste($id_paciente): bool {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT COUNT(*) FROM encaminhamento WHERE id_paciente = :id_paciente");
            $stmt->execute([':id_paciente' => $id_paciente]);
            return $stmt->fetchColumn() > 0;
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
