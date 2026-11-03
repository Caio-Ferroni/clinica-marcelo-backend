<?php
    class Paciente{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM paciente;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

    }
?>