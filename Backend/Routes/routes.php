<?php
// Backend/Routes/routes.php
// $router é injetado pelo index.php

// =========================================================
// MIDDLEWARE DE AUTENTICAÇÃO
// Protege todas as rotas privadas, exceto as telas de autenticação
// =========================================================
$router->before('GET|POST', '/(?!login|registrar|2fa|notificacoes(?:/|$)).*', function () {
    Auth::verificar();
});

// As rotas de notificações retornam 401 JSON pelo controller, sem redirecionar para HTML.
$router->get('/notificacoes/config', function () {
    (new NotificacaoController)->handle('config');
});
$router->post('/notificacoes/salvar-subscription', function () {
    (new NotificacaoController)->handle('save');
});
$router->post('/notificacoes/remover-subscription', function () {
    (new NotificacaoController)->handle('remove');
});

// =========================================================
// AUTH — rotas públicas
// =========================================================
$router->mount('/login', function () use ($router) {
    $router->get('/', function () {
        (new PsicologoController)->formLogin();
    });
    $router->post('/', function () {
        (new PsicologoController)->login();
    });
});

$router->mount('/registrar', function () use ($router) {
    $router->get('/', function () {
        (new PsicologoController)->formRegistrar();
    });
    $router->post('/', function () {
        (new PsicologoController)->registrar();
    });
});

$router->mount('/2fa', function () use ($router) {
    $router->get('/', function () {
        (new PsicologoController)->form2fa();
    });
    $router->post('/', function () {
        (new PsicologoController)->verificar2fa();
    });
});

$router->get('/logout', function () {
    (new PsicologoController)->logout();
});

// =========================================================
// PACIENTES
// =========================================================
$router->mount('/pacientes', function () use ($router) {
    // Lista todos os pacientes ativos
    $router->get('/', function () {
        (new PacienteController)->index();
    });

    // Formulário de criação de paciente (GET) + processamento (POST)
    $router->get('/criar', function () {
        require_once __DIR__ . '/../../Views/Paciente/paciente-create.php';
    });
    $router->post('/criar', function () {
        (new PacienteController)->criar();
    });

    // Visualizar um paciente específico
    $router->get('/(\d+)', function ($id) {
        (new PacienteController)->visualizar($id);
    });

    // Formulário de edição de paciente (GET) + processamento (POST)
    $router->get('/(\d+)/editar', function ($id) {
        (new PacienteController)->editar($id);
    });
    $router->post('/(\d+)/editar', function ($id) {
        (new PacienteController)->atualizar($id);
    });

    // Alternar status ativo/inativo do paciente
    $router->post('/(\d+)/status', function ($id) {
        // TODO: (new PacienteController)->atualizarStatus($id);
    });
});

// =========================================================
// CONSULTAS
// =========================================================
$router->mount('/consultas', function () use ($router) {
    // Visualizar calendário/lista de consultas
    $router->get('/', function () {
        // TODO: (new ConsultaController)->index();
        require_once __DIR__ . '/../../Views/Consulta/consulta.php';
    });

    // Gerar consultas do mês atual a partir dos agendamentos
    $router->post('/gerar', function () {
        (new ConsultaController)->gerarMesAtual();
    });
});

// =========================================================
// TAREFAS
// =========================================================
$router->mount('/tarefas', function () use ($router) {
    // Lista de tarefas
    $router->get('/', function () {
        // TODO: (new TarefaController)->index();
        require_once __DIR__ . '/../../Views/Tarefa/tarefa.php';
    });

    // Formulário de criação de tarefa (GET) + processamento (POST)
    $router->get('/criar', function () {
        (new TarefaController)->formCriar();
    });
    $router->post('/criar', function () {
        (new TarefaController)->criar();
    });
});

// =========================================================
// PERFIL DO PSICÓLOGO
// =========================================================
$router->get('/perfil', function () {
    (new PsicologoController)->perfil();
});

// =========================================================
// RELATÓRIOS
// =========================================================
$router->mount('/relatorios', function () use ($router) {
    $router->get('/', function () {
        require_once __DIR__ . '/../../Views/Relatorio/relatorio.php';
    });
    $router->get('/(\d+)', function ($id) {
        // TODO: (new RelatorioController)->visualizar($id);
        require_once __DIR__ . '/../../Views/Relatorio/relatorio-show.php';
    });
});

// =========================================================
// ROTA PADRÃO — redireciona a raiz para /pacientes
// =========================================================
$router->get('/', function () {
    header('Location: /pacientes');
    exit;
});

// =========================================================
// 404 — rota não encontrada
// =========================================================
$router->set404(function () {
    http_response_code(404);
    echo '404 — Página não encontrada';
});

