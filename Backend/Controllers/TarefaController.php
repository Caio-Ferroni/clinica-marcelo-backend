<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Tarefa.php';
require_once __DIR__ . '/../Support/Toast.php';
require_once __DIR__ . '/../Auth/Auth.php';

    class TarefaController{
        public Tarefa $tarefa;
        private function obterTarefa(): Tarefa {
            return $this->tarefa ??= new Tarefa;
        }

        public function index(){
            try {
                $tarefas = $this->obterTarefa()->listarTodos();
            } catch (PDOException $e) {
                $this->falhar('operation.failed', 503);
                return;
            }
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

        private function falhar(string $code, int $status): void {
            http_response_code($status);
            Toast::discardSuccess();
            $erro = Toast::message($code);
            $secao = 'tasks';
            $voltar = '/tarefas';
            require __DIR__ . '/../../Views/Shared/error.php';
        }

        public function visualizar($id): void {
            try {
                $tarefa = $this->obterTarefa()->visualizarTarefa($id)[0] ?? null;
                if ($tarefa === null) {
                    $this->falhar('task.missing', 404);
                    return;
                }
            } catch (PDOException $e) {
                $this->falhar('operation.failed', 503);
                return;
            }
            require __DIR__ . '/../../Views/Tarefa/tarefa-show.php';
        }

        public function editar($id): void {
            try {
                $tarefa = $this->obterTarefa()->visualizarTarefa($id)[0] ?? null;
                if ($tarefa === null) {
                    $this->falhar('task.missing', 404);
                    return;
                }
            } catch (PDOException $e) {
                $this->falhar('operation.failed', 503);
                return;
            }
            require __DIR__ . '/../../Views/Tarefa/tarefa-update.php';
        }

        public function atualizar($id): void {
            if (!Auth::validarCsrf($_POST['csrf_token'] ?? null)) {
                $this->falhar('validation.form_expired', 403);
                return;
            }
            try {
                $tarefa = $this->obterTarefa()->visualizarTarefa($id)[0] ?? null;
                if ($tarefa === null) {
                    $this->falhar('task.missing', 404);
                    return;
                }
                $valores = [];
                foreach (['titulo', 'conteudo', 'data_conclusao', 'status'] as $campo) {
                    $valores[$campo] = is_string($_POST[$campo] ?? null) ? trim($_POST[$campo]) : '';
                }
                $valores['id_tarefa'] = $id;
                if (in_array('', [$valores['titulo'], $valores['conteudo'], $valores['data_conclusao']], true)) {
                    $erro = Toast::message('task.required');
                } elseif (!in_array($valores['status'], Tarefa::STATUS, true)) {
                    $erro = Toast::message('task.status_invalid');
                } else {
                    $data = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valores['data_conclusao'])
                        ? DateTimeImmutable::createFromFormat('!Y-m-d', $valores['data_conclusao']) : false;
                    if (!$data || $data->format('Y-m-d') !== $valores['data_conclusao']) {
                        $erro = Toast::message('task.date');
                    }
                }
                if (isset($erro)) {
                    http_response_code(422);
                    Toast::discardSuccess();
                    require __DIR__ . '/../../Views/Tarefa/tarefa-update.php';
                    return;
                }
                $this->obterTarefa()->atualizarTarefa($id, $valores['titulo'], $valores['conteudo'], $valores['data_conclusao'], $valores['status']);
            } catch (PDOException $e) {
                $this->falhar('operation.failed', 500);
                return;
            }
            Toast::add('task.updated');
            header('Location: /tarefas', true, 303);
            exit;
        }
    }
