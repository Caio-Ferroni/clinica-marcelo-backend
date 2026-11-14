<?php 
require_once __DIR__ . '/../Config/Database.php';
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
            $dados = $this->carregarPaciente($id);
            if ($dados === null) {
                http_response_code(404);
                echo 'Paciente não encontrado.';
                return;
            }

            $paciente = $dados['paciente'];
            $perfil = $dados['perfil'];
            $agendamento = $dados['agendamento'];
            $encaminhamento = $dados['encaminhamento'];
            require_once __DIR__ . '/../../Views/Paciente/paciente-show.php';
        }

        public function editar($id){
            $dados = $this->carregarPaciente($id);
            if ($dados === null) {
                http_response_code(404);
                echo 'Paciente não encontrado.';
                return;
            }

            $paciente = $dados['paciente'];
            $perfil = $dados['perfil'];
            $agendamento = $dados['agendamento'];
            $encaminhamento = $dados['encaminhamento'];
            require_once __DIR__ . '/../../Views/Paciente/paciente-update.php';
        }

        private function carregarPaciente($id): ?array {
            $paciente = $this->paciente->visualizarPaciente($id)[0] ?? null;
            if ($paciente === null) {
                return null;
            }

            return [
                'paciente' => $paciente,
                'perfil' => $this->perfil->buscarPorPaciente($id),
                'agendamento' => $this->agendamento->buscarPorPaciente($id),
                'encaminhamento' => $this->encaminhamento->buscarPorPaciente($id)
            ];
        }

        public function criar(){
            $valores = array_filter($_POST, 'is_scalar');
            foreach (['nome', 'cpf', 'telefone', 'data_nascimento'] as $campo) {
                if (!isset($_POST[$campo]) || !is_string($_POST[$campo]) || trim($_POST[$campo]) === '') {
                    $erro = 'Preencha o nome, CPF, telefone e a data de nascimento do paciente.';
                    http_response_code(422);
                    require_once __DIR__ . '/../../Views/Paciente/paciente-create.php';
                    return;
                }
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
            $horario_termino = $_POST['horario_termino'] ?? '';
            if(isset($_POST['dia_semana']) && $_POST['dia_semana'] !== '' && !empty($_POST['repeticao']) &&
               !empty($_POST['horario_inicio']) && !empty($horario_termino) &&
               !empty($_POST['data_inicio'])){
                $this->agendamento->criarAgendamento(
                    $id_paciente,
                    $_POST['dia_semana'],
                    $_POST['repeticao'],
                    $_POST['horario_inicio'],
                    $horario_termino,
                    $_POST['data_inicio']
                );
            }

            $this->visualizar($id_paciente);
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
            $horario_termino = $_POST['horario_termino'] ?? '';
            if(isset($_POST['dia_semana']) && $_POST['dia_semana'] !== '' && !empty($_POST['repeticao']) &&
               !empty($_POST['horario_inicio']) && !empty($horario_termino) &&
               !empty($_POST['data_inicio'])){
                $this->agendamento->atualizarAgendamento(
                    $id,
                    $_POST['dia_semana'],
                    $_POST['repeticao'],
                    $_POST['horario_inicio'],
                    $horario_termino,
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
