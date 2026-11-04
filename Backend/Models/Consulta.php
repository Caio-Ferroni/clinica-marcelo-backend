<?php
    class Consulta{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM consulta;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function gerarConsultas(){

        }

        public function visualizarCalendario(){

        }

        public function visualizarConsulta($id){
            $sql = "SELECT * FROM consulta WHERE id_consulta = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function alterarStatus($id){

        }

        public function enviarNotificação(){

        }
        public function gerarRelatorio(){

        }
    }
?>