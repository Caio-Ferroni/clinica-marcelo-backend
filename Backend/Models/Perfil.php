<?php
    class Perfil{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM perfil;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }
        public function criarPerfil($id_paciente, $quadro, $descricao){
            $sql = "INSERT INTO perfil(id_paciente, quadro, descricao) VALUES (:id_paciente, :quadro, :descricao)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':quadro' => $quadro,
                ':descricao' => $descricao
            ]);
            $mensagem = "perfil criado com sucesso";
            return $mensagem;
        }

        // Cria ou atualiza o perfil do paciente (upsert por id_paciente)
        public function atualizarPerfil($id_paciente, $quadro, $descricao){
            if(!self::perfilExiste($id_paciente)){
                return $this->criarPerfil($id_paciente, $quadro, $descricao);
            } 
            
            $sql = "UPDATE perfil
                    SET quadro = :quadro, descricao = :descricao
                    WHERE id_paciente = :id_paciente";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':quadro'       => $quadro,
                ':descricao'    => $descricao,
                ':id_paciente'  => $id_paciente
            ]);
            return "Perfil atualizado";
        }

        public function visualizarPerfil($id){
            $sql = "SELECT * FROM perfil WHERE id_perfil = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
        return $stmt->fetchAll();
        }

        private static function perfilExiste($id_paciente): bool {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM perfil 
                WHERE id_paciente = :id_paciente");
            $stmt->execute([
                ':id_paciente' => $id_paciente

            ]);

            return $stmt->fetchColumn() > 0;
        }
    }
?>