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

        public function visualizarPaciente($id){
            $sql = "SELECT * FROM paciente WHERE id_paciente = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function criarPaciente($nome, $cpf, $telefone, $data_nascimento, $status){
            $sql = "INSERT INTO paciente(nome, cpf, telefone, data_nascimento, status) VALUES (:nome, :cpf, :telefone, :data_nascimento, :status_paciente)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome' => $nome,
                ':cpf' => $cpf,
                ':telefone' => $telefone,
                ':data_nascimento' => $data_nascimento,
                ':status_paciente' => $status
            ]);
            $mensagem = "paciente criado com sucesso";
            return $mensagem;
        }

        public function atualizarPaciente($id){

        }

        public function alterarStatus($id){

        }

    }
?>