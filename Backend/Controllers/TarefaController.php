<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Tarefa.php';

    class TarefaController{
        public Tarefa $tarefa;
        public function __construct()
        {
            $this->tarefa = new Tarefa;
        }

        public function criar(){
            if(!empty($_POST['titulo']) &&
                !empty($_POST['conteudo']) &&
                !empty($_POST['data_conclusao'])){
                   $mensagem = $this->tarefa->criarTarefa($_POST['titulo'], $_POST['conteudo'], $_POST['data_conclusao']);
                } else{
                    $mensagem = 'tarefa não inserida';
                }
            require_once __DIR__ . '/../../Views/Tarefa/tarefa-show.php';
        }
    
 }
?>