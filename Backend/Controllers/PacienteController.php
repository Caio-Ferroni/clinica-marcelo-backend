<?php 
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';

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

        public function show($id){
            $pacientes = $this->paciente->visualizarPaciente($id);
            require_once __DIR__ . '/../../Views/Paciente/paciente.php';
        }

        public function create(){

        }

        public function update(){

        }

        public function updateStatus(){
            
        }
    }
?>