<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Consulta.php';

    class ConsultaController{
        public Consulta $consulta;
        public function __construct()
        {
            $this->consulta = new Consulta;
        }

        public function index(){
            $consultas = $this->consulta->listarTodos();          
            require_once __DIR__ . '/../../Views/Consulta/consulta.php';
        }

        public function gerarMesAtual(){
            $consultasCriadas = $this->consulta->gerarConsultas();
            echo 'Foram criadas ' . $consultasCriadas . ' Consultas!';
            require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
        }

        public function mudarStatus($id, $status){
            $mensagem = $this->consulta->atualizarStatus($id, $status);
            echo $mensagem;
            require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
        }

        public function criarRelatorio($id, $relatorio){
            $relatorio = $this->consulta->gerarRelatorio($id, $relatorio);
            require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
        }

    }
?>
