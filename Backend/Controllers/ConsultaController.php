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

        public function mudarStatus($id, $status = null){
            if ($status === null) {
                $status = is_string($_POST['status'] ?? null) ? trim($_POST['status']) : '';
            }
            if ($status !== '') {
                $this->consulta->atualizarStatus($id, $status);
            }
            header('Location: /consultas', true, 303);
            exit;
        }

        public function criarRelatorio($id, $relatorio){
            $relatorio = $this->consulta->gerarRelatorio($id, $relatorio);
            require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
        }

        public function visualizar($id){
            $consultas = $this->consulta->visualizarConsulta($id);
            $consulta = $consultas[0] ?? null;
            if ($consulta === null) {
                http_response_code(404);
                echo 'Consulta não encontrada.';
                return;
            }
            require_once __DIR__ . '/../../Views/Consulta/consulta-show.php';
        }

        public function editar($id){
            $consultas = $this->consulta->visualizarConsulta($id);
            $consulta = $consultas[0] ?? null;
            if ($consulta === null) {
                http_response_code(404);
                echo 'Consulta não encontrada.';
                return;
            }
            require_once __DIR__ . '/../../Views/Consulta/consulta-update.php';
        }

        public function atualizar($id){
            $consultas = $this->consulta->visualizarConsulta($id);
            $consulta = $consultas[0] ?? null;
            if ($consulta === null) {
                http_response_code(404);
                echo 'Consulta não encontrada.';
                return;
            }

            $relatorio = is_string($_POST['relatorio'] ?? null) ? trim($_POST['relatorio']) : '';
            $this->consulta->gerarRelatorio($id, $relatorio);
            header('Location: /consultas', true, 303);
            exit;
        }

    }
?>