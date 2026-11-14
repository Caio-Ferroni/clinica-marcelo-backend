<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Auth/Auth.php';

class PsicologoController {
    public Psicologo $psicologo;

    public function __construct() {
        $this->psicologo = new Psicologo();
    }

    // Exibe o formulário de login (se já autenticado, vai direto para /pacientes)
    public function formLogin(): void {
        if (Auth::estaAutenticado()) {
            header('Location: /pacientes');
            exit;
        }

        require_once __DIR__ . '/../../Views/Auth/login.php';
    }

    // Processa a primeira etapa do login (e-mail + senha)
    public function login(): void {
        if (empty($_POST['email']) || empty($_POST['senha'])) {
            $erro = "Por favor, preencha o e-mail e a senha.";
            require_once __DIR__ . '/../../Views/Auth/login.php';
            return;
        }

        $usuario = $this->psicologo->fazerLogin($_POST['email'], $_POST['senha']);

        if ($usuario) {
            // Credenciais corretas! Gera o 2FA e redireciona para a tela do código
            Auth::gerarCodigo2FA($usuario);
            header('Location: /2fa');
            exit;
        } else {
            $erro = "E-mail ou senha incorretos.";
            require_once __DIR__ . '/../../Views/Auth/login.php';
        }
    }

    // Exibe a tela de inserção do código 2FA
    public function form2fa(): void {
        if (Auth::estaAutenticado()) {
            header('Location: /pacientes');
            exit;
        }

        // Se o usuário não passou pela etapa 1 de login, manda de volta para o login
        if (!Auth::aguardando2FA()) {
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../../Views/Auth/2fa.php';
    }

    // Processa a segunda etapa do login (validação do código 2FA)
    public function verificar2fa(): void {
        if (empty($_POST['codigo'])) {
            $erro = "Informe o código de verificação recebido por e-mail.";
            require_once __DIR__ . '/../../Views/Auth/2fa.php';
            return;
        }

        if (Auth::validarCodigo2FA($_POST['codigo'])) {
            // 2FA válido: usuário autenticado com sucesso!
            header('Location: /pacientes');
            exit;
        } else {
            $erro = "Código inválido ou expirado. Tente novamente.";
            require_once __DIR__ . '/../../Views/Auth/2fa.php';
        }
    }

    // Exibe o formulário de registro da conta única
    public function formRegistrar(): void {
        // Se já existe um psicólogo registrado, bloqueia o acesso à tela de cadastro
        if ($this->psicologo->existePsicologo()) {
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../../Views/Auth/register.php';
    }

    // Processa o primeiro cadastro do psicólogo
    public function registrar(): void {
        // Bloqueia caso já exista psicólogo cadastrado
        if ($this->psicologo->existePsicologo()) {
            header('Location: /login');
            exit;
        }

        if (!empty($_POST['email']) && !empty($_POST['senha']) && !empty($_POST['telefone'])) {
            $sucesso = $this->psicologo->criarPsicologo(
                $_POST['email'],
                $_POST['senha'],
                $_POST['telefone']
            );

            if ($sucesso) {
                header('Location: /login');
                exit;
            } else {
                $erro = "Não foi possível realizar o cadastro.";
            }
        } else {
            $erro = "Todos os campos são obrigatórios.";
        }

        require_once __DIR__ . '/../../Views/Auth/register.php';
    }

    // Visualizar perfil do psicólogo logado
    public function perfil(): void {
        Auth::verificar();
        $id = $_SESSION['psicologo_id'] ?? null;
        $perfil = $id ? $this->psicologo->visualizarPsicologo($id) : null;
        require_once __DIR__ . '/../../Views/Perfil/perfil-show.php';
    }

    // Encerra a sessão
    public function logout(): void {
        Auth::logout();
    }
}
