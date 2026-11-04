<?php 
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Perfil.php';
require_once __DIR__ . '/../Models/Encaminhamento.php';
require_once __DIR__ . '/../Models/Agendamento.php';

    Class PacienteController{

        public Paciente $paciente;
        public function __construct()
        {
            $this->paciente = new Paciente;
        }
        public function index(){
            $pacientes = $this->paciente->listarTodos();          
            require_once __DIR__ . '/../../Views/Paciente/paciente.php';
        }

        public function visualizar($id){
            $pacientes = $this->paciente->visualizarPaciente($id);
            require_once __DIR__ . '/../../Views/Paciente/paciente.php';
        }

        public function criar(){
            if(!empty($_POST['nome'] &&
                $_POST['cpf'] &&
                $_POST['telefone'] &&
                $_POST['data_nascimento'] &&
                $_POST['status'])){
                   $mensagem = $this->paciente->criarPaciente($_POST['nome'], $_POST['cpf'], $_POST['telefone'], $_POST['data_nascimento'], $_POST['status']);
                } else{
                    $mensagem = 'paciente não inserido';
                }
            require_once __DIR__ . '/../../Views/Paciente/paciente-show.php';
        }

        public function atualizar(){

        }

        public function atualizarStatus(){
            
        }
    }
?>