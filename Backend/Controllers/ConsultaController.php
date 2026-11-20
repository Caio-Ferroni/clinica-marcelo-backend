<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Support/Toast.php';
require_once __DIR__ . '/../Auth/Auth.php';

class ConsultaController {
    public Consulta $consulta;

    private function obterConsulta(): Consulta {
        return $this->consulta ??= new Consulta;
    }

    public function index(?string $erro = null, string $toastType = 'error'): void {
        $consultas = [];
        $indicadores = null;
        if ($erro !== null) Toast::discardSuccess();
        $statusPermitidos = Consulta::STATUS;
        $_SESSION['consulta_csrf'] ??= bin2hex(random_bytes(32));
        $csrf = $_SESSION['consulta_csrf'];

        try {
            $consultas = $this->obterConsulta()->listarTodos();
            $mesAtual = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m');
            $indicadores = ['mes' => 0, 'realizadas' => 0, 'canceladas' => 0];

            foreach ($consultas as $consulta) {
                if (substr((string) $consulta['data'], 0, 7) === $mesAtual) {
                    $indicadores['mes']++;
                }
                if ($consulta['status'] === 'Concluído') {
                    $indicadores['realizadas']++;
                }
                if ($consulta['status'] === 'Cancelado') {
                    $indicadores['canceladas']++;
                }
            }
        } catch (PDOException $e) {
            http_response_code(503);
            Toast::discardSuccess();
            $erro ??= Toast::message('consultation.load_failed');
            error_log('[Consultas] Falha ao carregar a lista.');
        }

        require __DIR__ . '/../../Views/Consulta/consulta.php';
    }

    public function gerarMesAtual() {
        try {
            $consultasCriadas = $this->obterConsulta()->gerarConsultas();
        } catch (PDOException $e) {
            http_response_code(500);
            $this->index(Toast::message('operation.failed'));
            return;
        }
        Toast::add($consultasCriadas > 0 ? 'consultation.generated' : 'consultation.none_generated');
        header('Location: /consultas', true, 303);
        exit;
    }

    public function mudarStatus($id): void {
        $token = $_POST['csrf_token'] ?? null;
        $tokenSessao = $_SESSION['consulta_csrf'] ?? null;

        if (!is_string($token) || !is_string($tokenSessao) || !hash_equals($tokenSessao, $token)) {
            http_response_code(403);
            $this->index(Toast::message('consultation.form_expired'), 'warning');
            return;
        }

        $status = $_POST['status'] ?? null;
        if (!is_string($status) || !in_array($status, Consulta::STATUS, true)) {
            http_response_code(422);
            $this->index(Toast::message('consultation.status_invalid'), 'warning');
            return;
        }

        try {
            if (!$this->obterConsulta()->atualizarStatus($id, $status)) {
                http_response_code(404);
                $this->index(Toast::message('consultation.missing'));
                return;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('[Consultas] Falha ao atualizar o status.');
            $this->index(Toast::message('consultation.save_failed'));
            return;
        }

        Toast::add('consultation.status_saved');
        header('Location: /consultas', true, 303);
        exit;
    }

    private function falhar(string $code, int $status): void {
        http_response_code($status);
        Toast::discardSuccess();
        $erro = Toast::message($code);
        $secao = 'appointments';
        $voltar = '/consultas';
        require __DIR__ . '/../../Views/Shared/error.php';
    }

    private function exibir($id, bool $editar): void {
        try {
            $consulta = $this->obterConsulta()->visualizarConsulta($id)[0] ?? null;
            if ($consulta === null) {
                $this->falhar('consultation.missing', 404);
                return;
            }
        } catch (PDOException $e) {
            $this->falhar('report.load_failed', 503);
            return;
        }
        $view = $editar ? 'consulta-update.php' : 'consulta-show.php';
        require __DIR__ . '/../../Views/Consulta/' . $view;
    }

    public function visualizarRelatorio($id): void {
        $this->visualizar($id);
    }

    public function visualizar($id): void {
        $this->exibir($id, false);
    }

    public function editar($id): void {
        $this->exibir($id, true);
    }

    public function atualizar($id): void {
        if (!Auth::validarCsrf($_POST['csrf_token'] ?? null)) {
            $this->falhar('validation.form_expired', 403);
            return;
        }
        try {
            $consulta = $this->obterConsulta()->visualizarConsulta($id)[0] ?? null;
            if ($consulta === null) {
                $this->falhar('consultation.missing', 404);
                return;
            }
            if (!is_string($_POST['relatorio'] ?? null)) {
                http_response_code(422);
                Toast::discardSuccess();
                $erro = Toast::message('validation.fields');
                require __DIR__ . '/../../Views/Consulta/consulta-update.php';
                return;
            }
            $this->obterConsulta()->gerarRelatorio($id, $_POST['relatorio']);
        } catch (PDOException $e) {
            $this->falhar('operation.failed', 500);
            return;
        }
        Toast::add('report.saved');
        header('Location: /consultas/' . $id, true, 303);
        exit;
    }
}
