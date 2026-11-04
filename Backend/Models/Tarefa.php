<?php
    class Tarefa{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM tarefa;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function criarTarefa($titulo, $descricao){
            $sql = "INSERT INTO tarefa(titulo, descricao) VALUES (:titulo, :descricao)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':descricao' => $descricao
            ]);
            $mensagem = "tarefa criada com sucesso";
            return $mensagem;
        }

        public function atualizarTarefa($id, $titulo, $descricao){

        }
        public function visualizarTarefa($id){
            $sql = "SELECT * FROM tarefa WHERE id_tarefa = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function atualizarStatus($id){

        }
    }
?>