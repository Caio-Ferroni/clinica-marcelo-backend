<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Tarefa.php';

    class TarefaController{
        public Tarefa $tarefa;
        public function __construct()
        {
            $this->tarefa = new Tarefa;
        }

        public function index(){
            $tarefas = $this->tarefa->listarTodos();          
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
                $erro = 'Preencha o título, o conteúdo e a data de conclusão da tarefa.';
            } else {
                $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valores['data_conclusao'])
                    ? DateTime::createFromFormat('!Y-m-d', $valores['data_conclusao']) : false;
                if (!$data || $data->format('Y-m-d') !== $valores['data_conclusao']) {
                    $erro = 'Informe uma data de conclusão válida.';
                }
            }

            if (isset($erro)) {
                http_response_code(422);
                require_once __DIR__ . '/../../Views/Tarefa/tarefa-create.php';
                return;
            }

            $this->tarefa->criarTarefa($valores['titulo'], $valores['conteudo'], $valores['data_conclusao']);
            header('Location: /tarefas', true, 303);
            exit;
        }

        public function editar($id){
            $tarefas = $this->tarefa->visualizarTarefa($id);
            $tarefa = $tarefas[0] ?? null;
            if ($tarefa === null) {
                http_response_code(404);
                echo 'Tarefa não encontrada.';
                return;
            }

            require_once __DIR__ . '/../../Views/Tarefa/tarefa-update.php';
        }

        public function atualizar($id){
            $tarefas = $this->tarefa->visualizarTarefa($id);
            $tarefa = $tarefas[0] ?? null;
            if ($tarefa === null) {
                http_response_code(404);
                echo 'Tarefa não encontrada.';
                return;
            }

            $valores = [];
            foreach (['titulo', 'conteudo', 'data_conclusao'] as $campo) {
                $valores[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
            }
            $valores['status'] = is_string($_POST['status'] ?? null) ? trim($_POST['status']) : ($tarefa['status'] ?? 'Pendente');
            $valores['id_tarefa'] = $id;

            if (in_array('', [$valores['titulo'], $valores['conteudo'], $valores['data_conclusao']], true)) {
                $erro = 'Preencha o título, o conteúdo e a data de conclusão da tarefa.';
            } else {
                $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $valores['data_conclusao'])
                    ? DateTime::createFromFormat('!Y-m-d', $valores['data_conclusao']) : false;
                if (!$data || $data->format('Y-m-d') !== $valores['data_conclusao']) {
                    $erro = 'Informe uma data de conclusão válida.';
                }
            }

            if (isset($erro)) {
                http_response_code(422);
                require_once __DIR__ . '/../../Views/Tarefa/tarefa-update.php';
                return;
            }

            $this->tarefa->atualizarTarefa($id, $valores['titulo'], $valores['conteudo'], $valores['data_conclusao'], $valores['status']);
            header('Location: /tarefas', true, 303);
            exit;
        }
    
 }
?>
