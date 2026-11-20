<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Models/Tarefa.php';
require_once __DIR__ . '/../Support/Toast.php';

class DashboardController {
    public Paciente $paciente;
    public Consulta $consulta;
    public Tarefa $tarefa;

    private function prepararModelos(): void {
        Database::getConnection(true);
        $this->paciente = new Paciente;
        $this->consulta = new Consulta;
        $this->tarefa = new Tarefa;
    }

    public function index() {
        try {
            $this->prepararModelos();
            $pacientes = $this->paciente->listarTodos();
            $consultas = $this->consulta->listarTodos();
            $tarefas = $this->tarefa->listarTodos();
        } catch (PDOException $e) {
            http_response_code(503);
            Toast::discardSuccess();
            $erro = Toast::message('operation.failed');
            $secao = 'home';
            $voltar = '/dashboard';
            require __DIR__ . '/../../Views/Shared/error.php';
            return;
        }

        require_once __DIR__ . '/../../Views/Dashboard/dashboard.php';
    }
}
