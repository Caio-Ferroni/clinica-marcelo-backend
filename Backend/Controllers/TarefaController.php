<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Tarefa.php';
require_once __DIR__ . '/../Support/Toast.php';

    class TarefaController{
        public Tarefa $tarefa;
        private function obterTarefa(): Tarefa {
            return $this->tarefa ??= new Tarefa;
        }

        public function index(){
            $tarefas = $this->obterTarefa()->listarTodos();
            require_once __DIR__ . '/../../Views/Tarefa/tarefa.php';
        }

        public function formCriar(){
            require_once __DIR__ . '/../../Views/Tarefa/tarefa-create.php';
        }

        public function criar(){
            $valores = [];
            foreach (['titulo', 'conteudo', 'data_conclusao'] as $campo) {
                $valores[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
            }

            if (in_array('', $valores, true)) {
                $erro = Toast::message('task.required');
            } else {
                $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valores['data_conclusao'])
                    ? DateTime::createFromFormat('!Y-m-d', $valores['data_conclusao']) : false;
                if (!$data || $data->format('Y-m-d') !== $valores['data_conclusao']) {
                    $erro = Toast::message('task.date');
                }
            }

            if (isset($erro)) {
                $toastType = 'warning';
                Toast::discardSuccess();
                http_response_code(422);
                require_once __DIR__ . '/../../Views/Tarefa/tarefa-create.php';
                return;
            }

            try {
                $this->obterTarefa()->criarTarefa($valores['titulo'], $valores['conteudo'], $valores['data_conclusao']);
            } catch (PDOException $e) {
                $erro = Toast::message('operation.failed');
                Toast::discardSuccess();
                http_response_code(500);
                require __DIR__ . '/../../Views/Tarefa/tarefa-create.php';
                return;
            }
            Toast::add('task.created');
            header('Location: /tarefas', true, 303);
            exit;
        }
    
 }
?>
