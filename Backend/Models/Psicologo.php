<?php
    class Psicologo{
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection();
        }

        // Verifica se já existe um psicólogo cadastrado no sistema (regra de conta única)
        public function existePsicologo(): bool {
            $sql = 'SELECT COUNT(*) FROM psicologo;';
            $stmt = $this->db->query($sql);
            return (int) $stmt->fetchColumn() > 0;
        }

        public function listarTodos(){
            $sql = 'SELECT id_psicologo, email, telefone FROM psicologo;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }
        
        // Criação permitida apenas se ainda não houver nenhum psicólogo cadastrado
        public function criarPsicologo($email, $senha): bool {
            if ($this->existePsicologo()) {
                return false; // Bloqueia a criação de mais de uma conta
            }

            $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

            $sql = "INSERT INTO psicologo (email, senha) VALUES (:email, :senha)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':email'    => $email,
                ':senha'    => $senhaHash
            ]);
        }

        // Busca o psicólogo pelo e-mail e valida a senha com o hash criptografado
        public function fazerLogin($email, $senha) {
            $sql = "SELECT * FROM psicologo WHERE email = :email LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':email' => $email]);
            $psicologo = $stmt->fetch();

            if ($psicologo && password_verify($senha, $psicologo['senha'])) {
                return $psicologo;
            }

            return false;
        }

        public function atualizarPsicologo($id, $email, $senha = null, $telefone = null){
            if (!empty($senha)) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $sql = "UPDATE psicologo SET email = :email, senha = :senha, telefone = :telefone WHERE id_psicologo = :id";
                $params = [
                    ':email'    => $email,
                    ':senha'    => $senhaHash,
                    ':telefone' => $telefone,
                    ':id'       => $id
                ];
            } else {
                $sql = "UPDATE psicologo SET email = :email, telefone = :telefone WHERE id_psicologo = :id";
                $params = [
                    ':email'    => $email,
                    ':telefone' => $telefone,
                    ':id'       => $id
                ];
            }

            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        }

        public function visualizarPsicologo($id){
            $sql = "SELECT id_psicologo, email, telefone FROM psicologo WHERE id_psicologo = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetch();
        }

    }
?>