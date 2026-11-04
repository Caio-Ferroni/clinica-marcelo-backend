<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Tarefa.php';

    class TarefaController{
        public Tarefa $tarefa;
        public function __construct()
        {
            $this->tarefa = new Tarefa;
    }
 }
?>