<?php
session_start();

// Autoloader do Composer (bramus/router e futuras dependências)
require_once __DIR__ . '/vendor/autoload.php';

// Config
require_once __DIR__ . '/Backend/Config/Database.php';

// Models
require_once __DIR__ . '/Backend/Models/Paciente.php';
require_once __DIR__ . '/Backend/Models/Perfil.php';
require_once __DIR__ . '/Backend/Models/Encaminhamento.php';
require_once __DIR__ . '/Backend/Models/Agendamento.php';
require_once __DIR__ . '/Backend/Models/Consulta.php';
require_once __DIR__ . '/Backend/Models/Tarefa.php';
require_once __DIR__ . '/Backend/Models/Psicologo.php';

// Controllers
require_once __DIR__ . '/Backend/Controllers/PacienteController.php';
require_once __DIR__ . '/Backend/Controllers/ConsultaController.php';
require_once __DIR__ . '/Backend/Controllers/TarefaController.php';
require_once __DIR__ . '/Backend/Controllers/PsicologoController.php';
ob_start();
require_once __DIR__ . '/Backend/Controllers/NotificacaoController.php';
ob_end_clean();

// Auth
require_once __DIR__ . '/Backend/Auth/Auth.php';

// Instancia o router e carrega as rotas
$router = new \Bramus\Router\Router();
require_once __DIR__ . '/Backend/Routes/routes.php';

$router->run();
