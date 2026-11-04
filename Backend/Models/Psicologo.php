<?php
    class Psicologo{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM paciente;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }
        
        public function criarPsicologo($email, $senha, $telefone){

        }
        public function atualizarPsicologo($id, $email, $senha, $telefone){

        }

        public function fazerLogin($email, $senha){

        }

        public function visualizarPsicologo($id){
            $sql = "SELECT * FROM psicologo WHERE id_psicologo = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

    }
?>