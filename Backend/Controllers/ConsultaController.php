<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Consulta.php';

class ConsultaController {
    public Consulta $consulta;

    private function obterConsulta(): Consulta {
        return $this->consulta ??= new Consulta;
    }

    public function index(?string $erro = null): void {
        $consultas = [];
        $indicadores = null;
        $mensagem = $erro === null ? ($_SESSION['consulta_mensagem'] ?? null) : null;
        unset($_SESSION['consulta_mensagem']);
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
            $mensagem = null;
            $erro ??= 'Não foi possível carregar as consultas. Tente novamente.';
            error_log('[Consultas] Falha ao carregar a lista.');
        }

        require __DIR__ . '/../../Views/Consulta/consulta.php';
    }

    public function gerarMesAtual() {
        $consultasCriadas = $this->obterConsulta()->gerarConsultas();
        echo 'Foram criadas ' . $consultasCriadas . ' Consultas!';
        require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
    }

    public function mudarStatus($id): void {
        $token = $_POST['csrf_token'] ?? null;
        $tokenSessao = $_SESSION['consulta_csrf'] ?? null;

        if (!is_string($token) || !is_string($tokenSessao) || !hash_equals($tokenSessao, $token)) {
            http_response_code(403);
            $this->index('O formulário expirou. Tente salvar o status novamente.');
            return;
        }

        $status = $_POST['status'] ?? null;
        if (!is_string($status) || !in_array($status, Consulta::STATUS, true)) {
            http_response_code(422);
            $this->index('Selecione um status válido para a consulta.');
            return;
        }

        try {
            if (!$this->obterConsulta()->atualizarStatus($id, $status)) {
                http_response_code(404);
                $this->index('Consulta não encontrada.');
                return;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('[Consultas] Falha ao atualizar o status.');
            $this->index('Não foi possível salvar o status. Tente novamente.');
            return;
        }

        $_SESSION['consulta_mensagem'] = 'Status da consulta atualizado com sucesso.';
        header('Location: /consultas', true, 303);
        exit;
    }

    public function visualizarRelatorio($id): void {
        $consulta = null;
        $erro = null;

        try {
            $consulta = $this->obterConsulta()->visualizarConsulta($id)[0] ?? null;
            if ($consulta === null) {
                http_response_code(404);
                $erro = 'Consulta não encontrada.';
            }
        } catch (PDOException $e) {
            http_response_code(503);
            $erro = 'Não foi possível carregar o relatório. Tente novamente.';
            error_log('[Consultas] Falha ao carregar o relatório.');
        }

        require __DIR__ . '/../../Views/Relatorio/relatorio-show.php';
    }
}
