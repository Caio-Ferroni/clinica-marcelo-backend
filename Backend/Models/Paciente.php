<?php
    class Paciente{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }
        

        public function listarTodos(?string $status = 'Ativo'): array {
            $sql = 'SELECT * FROM paciente';
            if ($status !== null) $sql .= ' WHERE status = :status';
            $sql .= ' ORDER BY id_paciente';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($status === null ? [] : [':status' => $status]);
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

        public function criarPaciente($nome, $cpf, $telefone, $data_nascimento): int {
            $sql = "INSERT INTO paciente(nome, cpf, telefone, data_nascimento) VALUES (:nome, :cpf, :telefone, :data_nascimento)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome'            => $nome,
                ':cpf'             => preg_replace('/\D/', '', (string) $cpf),
                ':telefone'        => $telefone,
                ':data_nascimento' => $data_nascimento
            ]);
            // Retorna o ID do paciente recém-criado para associar as entidades dependentes
            return (int) $this->db->lastInsertId();
        }

        public function atualizarPaciente($id, $nome, $cpf, $telefone, $data_nascimento){
            $sql = "UPDATE paciente
                    SET nome = :nome, cpf = :cpf, telefone = :telefone, data_nascimento = :data_nascimento
                    WHERE id_paciente = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':nome'            => $nome,
                ':cpf'             => preg_replace('/\D/', '', (string) $cpf),
                ':telefone'        => $telefone,
                ':data_nascimento' => $data_nascimento,
                ':id'              => $id
            ]);
        }

        public function atualizarStatus($id, $status){
            $sql = "UPDATE paciente SET status = :status WHERE id_paciente = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':status' => $status,
                ':id' => $id
            ]);

            $mensagem = "Status do paciente atualizado com sucesso!";
            return $mensagem;
        }

    }
?>
