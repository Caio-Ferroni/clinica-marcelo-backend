<?php
    class Tarefa{
        public const STATUS = ['Pendente', 'Concluída', 'Cancelada'];
        protected PDO $db;

        public function __construct() {
            $this->db = Database::getConnection(true);
        }

        public function listarTodos(){
            $sql = 'SELECT * FROM tarefa ORDER BY data_conclusao ASC, id_tarefa DESC;';
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll();
        }

        public function criarTarefa($titulo, $conteudo, $data_conclusao){
            $sql = "INSERT INTO tarefa(titulo, conteudo, data_conclusao) VALUES (:titulo, :conteudo, :data_conclusao)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':conteudo' => $conteudo,
                ':data_conclusao' => $data_conclusao
            ]);
            $mensagem = "tarefa criada com sucesso";
            return $mensagem;
        }

        public function atualizarTarefa($id, $titulo, $conteudo, $data_conclusao = null, $status = null){
            if ($data_conclusao !== null && $status !== null) {
                $sql = "UPDATE tarefa
                        SET titulo = :titulo, conteudo = :conteudo, data_conclusao = :data_conclusao, status = :status
                        WHERE id_tarefa = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id'             => $id,
                    ':titulo'         => $titulo,
                    ':conteudo'       => $conteudo,
                    ':data_conclusao' => $data_conclusao,
                    ':status'         => $status
                ]);
            } elseif ($data_conclusao !== null) {
                $sql = "UPDATE tarefa
                        SET titulo = :titulo, conteudo = :conteudo, data_conclusao = :data_conclusao
                        WHERE id_tarefa = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id'             => $id,
                    ':titulo'         => $titulo,
                    ':conteudo'       => $conteudo,
                    ':data_conclusao' => $data_conclusao
                ]);
            } else {
                $sql = "UPDATE tarefa
                        SET titulo = :titulo, conteudo = :conteudo
                        WHERE id_tarefa = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id'       => $id,
                    ':titulo'   => $titulo,
                    ':conteudo' => $conteudo
                ]);
            }
        }
        public function visualizarTarefa($id){
            $sql = "SELECT * FROM tarefa WHERE id_tarefa = :id";
            $stmt = $this->db->prepare($sql); 
            $stmt->execute([
                ':id' => $id
            ]);
            return $stmt->fetchAll();
        }

        public function atualizarStatus($id, $status){
            $sql = "UPDATE tarefa
                    SET status = :status
                    WHERE id_tarefa = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id'       => $id,
                ':status' => $status
            ]);
        }
    }
?>
