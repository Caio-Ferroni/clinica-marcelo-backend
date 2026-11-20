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

    private function indicadores(array $consultas): array {
        $mesAtual = (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m');
        $indicadores = ['mes' => 0, 'realizadas' => 0, 'canceladas' => 0];
        foreach ($consultas as $consulta) {
            if (substr((string) $consulta['data'], 0, 7) === $mesAtual) $indicadores['mes']++;
            if ($consulta['status'] === 'Concluído') $indicadores['realizadas']++;
            if ($consulta['status'] === 'Cancelado') $indicadores['canceladas']++;
        }
        return $indicadores;
    }

    private function respostaStatus(string $code, int $status, array $dados = []): void {
        if ($status >= 400) Toast::discardSuccess();
        if (str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            echo json_encode(['ok' => $status < 400, 'toast_code' => $code] + $dados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            return;
        }
        if ($status >= 400) {
            http_response_code($status);
            $this->index(Toast::message($code), Toast::catalog()[$code]['type']);
            return;
        }
        Toast::add($code);
        header('Location: /consultas', true, 303);
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
            $indicadores = $this->indicadores($consultas);
        } catch (PDOException $e) {
            http_response_code(503);
            Toast::discardSuccess();
            $erro ??= Toast::message('consultation.load_failed');
            error_log('[Consultas] Falha ao carregar a lista.');
        }

        require __DIR__ . '/../../Views/Consulta/consulta.php';
    }

    public function gerarMesAtual() {
        $token = $_POST['csrf_token'] ?? null;
        $tokenSessao = $_SESSION['consulta_csrf'] ?? null;
        if (!is_string($token) || !is_string($tokenSessao) || !hash_equals($tokenSessao, $token)) {
            http_response_code(403);
            $this->index(Toast::message('validation.form_expired'), 'warning');
            return;
        }
        try {
            $consultasCriadas = $this->obterConsulta()->gerarConsultas();
        } catch (PDOException $e) {
            http_response_code(500);
            $this->index(Toast::message('consultation.generate_failed'));
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
            $this->respostaStatus('consultation.form_expired', 403);
            return;
        }

        $status = $_POST['status'] ?? null;
        if (!is_string($status) || !in_array($status, Consulta::STATUS, true)) {
            $this->respostaStatus('consultation.status_invalid', 422);
            return;
        }

        try {
            if (!$this->obterConsulta()->atualizarStatus($id, $status)) {
                $this->respostaStatus('consultation.missing', 404);
                return;
            }
            $dados = [];
            if (str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
                $consulta = $this->obterConsulta()->visualizarConsulta($id)[0] ?? null;
                if ($consulta === null) {
                    $this->respostaStatus('consultation.missing', 404);
                    return;
                }
                $dados = [
                    'id' => (int) $id,
                    'status' => $consulta['status'],
                    'pode_editar' => Consulta::podeEditarRelatorio($consulta),
                    'indicadores' => $this->indicadores($this->obterConsulta()->listarTodos())
                ];
            }
        } catch (PDOException $e) {
            error_log('[Consultas] Falha ao atualizar o status.');
            $this->respostaStatus('consultation.save_failed', 500);
            return;
        }

        $this->respostaStatus('consultation.status_saved', 200, $dados);
    }

    private function falhar(string $code, int $status, string $toastType = 'error'): void {
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
            if ($editar && !Consulta::podeEditarRelatorio($consulta)) {
                $this->falhar('report.not_completed', 409, 'warning');
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
            if (!Consulta::podeEditarRelatorio($consulta)) {
                $this->falhar('report.not_completed', 409, 'warning');
                return;
            }
            if (!is_string($_POST['relatorio'] ?? null)) {
                http_response_code(422);
                Toast::discardSuccess();
                $erro = Toast::message('validation.fields');
                require __DIR__ . '/../../Views/Consulta/consulta-update.php';
                return;
            }
            if (!$this->obterConsulta()->gerarRelatorio($id, $_POST['relatorio'])) {
                $this->falhar('report.not_completed', 409, 'warning');
                return;
            }
        } catch (PDOException $e) {
            $this->falhar('operation.failed', 500);
            return;
        }
        Toast::add('report.saved');
        header('Location: /consultas/' . $id, true, 303);
        exit;
    }
}
