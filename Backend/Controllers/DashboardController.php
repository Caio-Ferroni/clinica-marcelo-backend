<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Consulta.php';
require_once __DIR__ . '/../Models/Tarefa.php';

class DashboardController {
    public Paciente $paciente;
    public Consulta $consulta;
    public Tarefa $tarefa;

    public function __construct() {
        $this->paciente = new Paciente;
        $this->consulta = new Consulta;
        $this->tarefa = new Tarefa;
    }

    public function index() {
        $pacientes = $this->paciente->listarTodos();
        $consultas = $this->consulta->listarTodos();
        $tarefas = $this->tarefa->listarTodos();

        require_once __DIR__ . '/../../Views/Dashboard/dashboard.php';
    }
}
