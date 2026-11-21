<?php
// Backend/Routes/routes.php
// $router é injetado pelo index.php

// =========================================================
// MIDDLEWARE DE AUTENTICAÇÃO
// Protege todas as rotas privadas, exceto as telas de autenticação
// =========================================================
$router->before('GET|POST', '/(?!login|registrar|2fa|notificacoes(?:/|$)).*', function () {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && preg_match('~^/(?:consultas/\d+/status|pacientes/horarios-livres)/?$~', parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '')
        && str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
        && !Auth::estaAutenticado()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(['ok' => false, 'toast_code' => 'auth.required']);
        exit;
    }
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
    $router->post('/horarios-livres', function () {
        (new PacienteController)->horariosLivres();
    });
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
        (new ConsultaController)->index();
    });

    // Gerar consultas do mês atual a partir dos agendamentos
    $router->post('/gerar', function () {
        (new ConsultaController)->gerarMesAtual();
    });

    // Visualizar uma consulta específica
    $router->get('/(\d+)', function ($id) {
        (new ConsultaController)->visualizar($id);
    });

    // Formulário de edição de consulta (apenas relatório) (GET) + processamento (POST)
    $router->get('/(\d+)/editar', function ($id) {
        (new ConsultaController)->editar($id);
    });
    $router->post('/(\d+)/editar', function ($id) {
        (new ConsultaController)->atualizar($id);
    });

    // Alterar status da consulta
    $router->post('/(\d+)/status', function ($id) {
        (new ConsultaController)->mudarStatus($id);
    });
});

// =========================================================
// TAREFAS
// =========================================================
$router->mount('/tarefas', function () use ($router) {
    // Lista de tarefas
    $router->get('/', function () {
        (new TarefaController)->index();
    });

    // Formulário de criação de tarefa (GET) + processamento (POST)
    $router->get('/criar', function () {
        (new TarefaController)->formCriar();
    });
    $router->post('/criar', function () {
        (new TarefaController)->criar();
    });

    // Formulário de edição de tarefa (GET) + processamento (POST)
    $router->get('/(\d+)/editar', function ($id) {
        (new TarefaController)->editar($id);
    });
    $router->post('/(\d+)/editar', function ($id) {
        (new TarefaController)->atualizar($id);
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
        header('Location: /consultas', true, 302);
        exit;
    });
    $router->get('/(\d+)', function ($id) {
        (new ConsultaController)->visualizarRelatorio($id);
    });
});

// =========================================================
// DASHBOARD
// =========================================================
$router->get('/dashboard', function () {
    (new DashboardController)->index();
});

// =========================================================
// ROTA PADRÃO — redireciona a raiz para /dashboard
// =========================================================
$router->get('/', function () {
    header('Location: /dashboard');
    exit;
});

// =========================================================
// 404 — rota não encontrada
// =========================================================
$router->set404(function () {
    http_response_code(404);
    echo '404 — Página não encontrada';
});
