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
            $sql = "INSERT INTO perfil(id_paciente, quadro, descricao, status) VALUES (:id_paciente, :quadro, :descricao)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_paciente' => $id_paciente,
                ':quadro' => $quadro,
                ':descricao' => $descricao
            ]);
            $mensagem = "perfil criado com sucesso";
            return $mensagem;
        }

        public function atualizarPerfil($id, $quadro, $descricao){

        }

        public function visualizarPerfil($id){
            $sql = "SELECT * FROM perfil WHERE id_perfil = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
        return $stmt->fetchAll();
        }
    }
?>