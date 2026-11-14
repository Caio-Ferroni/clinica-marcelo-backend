<?php 
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../Models/Paciente.php';
require_once __DIR__ . '/../Models/Perfil.php';
require_once __DIR__ . '/../Models/Encaminhamento.php';
require_once __DIR__ . '/../Models/Agendamento.php';

    Class PacienteController{

        public Paciente $paciente;
        public Perfil $perfil;
        public Encaminhamento $encaminhamento;
        public Agendamento $agendamento;

        protected PDO $db;
        public function __construct()
        {
            $this->perfil = new Perfil;
            $this->encaminhamento = new Encaminhamento;
            $this->agendamento = new Agendamento;
            $this->paciente = new Paciente;
            $this->db = Database::getConnection();
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
            // Paciente é obrigatório — sem esses campos nada é criado
            if(empty($_POST['nome']) || empty($_POST['cpf']) ||
               empty($_POST['telefone']) || empty($_POST['data_nascimento'])){
                // TODO: retornar mensagem de erro para a view
                require_once __DIR__ . '/../../Views/Paciente/paciente-show.php';
                return;
            }

            // Cria o paciente e obtém o ID gerado para associar as entidades dependentes
            $id_paciente = $this->paciente->criarPaciente(
                $_POST['nome'],
                $_POST['cpf'],
                $_POST['telefone'],
                $_POST['data_nascimento']
            );

            // Perfil — opcional: criado somente se todos os campos forem preenchidos
            if(!empty($_POST['quadro']) && !empty($_POST['descricao'])){
                $this->perfil->criarPerfil(
                    $id_paciente,
                    $_POST['quadro'],
                    $_POST['descricao']
                );
            }

            // Encaminhamento — opcional: criado somente se todos os campos forem preenchidos
            if(!empty($_POST['historico_paciente']) && !empty($_POST['tipo_encaminhamento'])){
                $this->encaminhamento->criarEncaminhamento(
                    $id_paciente,
                    $_POST['historico_paciente'],
                    $_POST['tipo_encaminhamento']
                );
            }

            // Agendamento — opcional: todos os campos são necessários para ter sentido
            if(!empty($_POST['dia_semana'])    && !empty($_POST['repeticao']) &&
               !empty($_POST['horario_inicio']) && !empty($_POST['horario_fim']) &&
               !empty($_POST['data_inicio'])){
                $this->agendamento->criarAgendamento(
                    $id_paciente,
                    $_POST['dia_semana'],
                    $_POST['repeticao'],
                    $_POST['horario_inicio'],
                    $_POST['horario_fim'],
                    $_POST['data_inicio']
                );
            }

            require_once __DIR__ . '/../../Views/Paciente/paciente-show.php';
        }
        

        public function atualizar($id){
            // Atualiza os dados básicos do paciente se todos os campos forem enviados
            if(!empty($_POST['nome']) && !empty($_POST['cpf']) &&
               !empty($_POST['telefone']) && !empty($_POST['data_nascimento'])){
                $this->paciente->atualizarPaciente(
                    $id,
                    $_POST['nome'],
                    $_POST['cpf'],
                    $_POST['telefone'],
                    $_POST['data_nascimento']
                );
            }

            // Perfil — upsert se todos os campos necessários forem preenchidos
            if(!empty($_POST['quadro']) && !empty($_POST['descricao'])){
                $this->perfil->atualizarPerfil(
                    $id,
                    $_POST['quadro'],
                    $_POST['descricao'] 
                );
            }

            // Encaminhamento — upsert se todos os campos necessários forem preenchidos
            if(!empty($_POST['historico_paciente']) && !empty($_POST['tipo_encaminhamento'])){
                $this->encaminhamento->atualizarEncaminhamento(
                    $id,
                    $_POST['historico_paciente'],
                    $_POST['tipo_encaminhamento']
                );
            }

            // Agendamento — upsert se todos os campos necessários forem preenchidos
            if(!empty($_POST['dia_semana']) && !empty($_POST['repeticao']) &&
               !empty($_POST['horario_inicio']) && !empty($_POST['horario_fim']) &&
               !empty($_POST['data_inicio'])){
                $this->agendamento->atualizarAgendamento(
                    $id,
                    $_POST['dia_semana'],
                    $_POST['repeticao'],
                    $_POST['horario_inicio'],
                    $_POST['horario_fim'],
                    $_POST['data_inicio']
                );
            }

            require_once __DIR__ . '/../../Views/Paciente/paciente-update.php';
        }

        public function mudarStatus($id, $status){
            $mensagem = $this->paciente->atualizarStatus($id, $status);
            echo $mensagem;
            require_once __DIR__ . '/../../Views/Paciente/paciente-show.php';
        }
    }
?>