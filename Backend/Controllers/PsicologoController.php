<?php
require_once __DIR__ . '/../Config/Database.php';
require_once __DIR__ . '/../Models/Psicologo.php';
require_once __DIR__ . '/../Auth/Auth.php';

class PsicologoController {
    public Psicologo $psicologo;

    private function obterPsicologo(): Psicologo {
        return $this->psicologo ??= new Psicologo;
    }

    private function mostrarErro(string $view, string $code, int $status = 422): void {
        $erro = Toast::message($code);
        $toastType = Toast::catalog()[$code]['type'];
        Toast::discardSuccess();
        http_response_code($status);
        require __DIR__ . '/../../Views/Auth/' . $view . '.php';
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
        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        if ($email === '' || $senha === '') {
            $this->mostrarErro('login', 'validation.required');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarErro('login', 'validation.email');
            return;
        }

        try {
            $usuario = $this->obterPsicologo()->fazerLogin($email, $senha);
        } catch (PDOException $e) {
            $this->mostrarErro('login', 'operation.failed', 503);
            return;
        }

        if ($usuario) {
            if (!Auth::gerarCodigo2FA($usuario)) {
                $this->mostrarErro('login', 'auth.code_failed', 503);
                return;
            }

            Toast::add('auth.code_sent');
            header('Location: /2fa');
            exit;
        } else {
            $this->mostrarErro('login', 'auth.credentials', 401);
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
            Toast::add('auth.required');
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../../Views/Auth/2fa.php';
    }

    // Processa a segunda etapa do login (validação do código 2FA)
    public function verificar2fa(): void {
        $codigo = is_string($_POST['codigo'] ?? null) ? trim($_POST['codigo']) : '';
        if (!preg_match('/^[0-9]{6}$/', $codigo)) {
            $this->mostrarErro('2fa', 'validation.code');
            return;
        }

        if (Auth::validarCodigo2FA($codigo)) {
            // 2FA válido: usuário autenticado com sucesso!
            Toast::add('auth.login');
            header('Location: /pacientes');
            exit;
        } else {
            $this->mostrarErro('2fa', 'auth.code_invalid');
        }
    }

    // Exibe o formulário de registro da conta única
    public function formRegistrar(): void {
        // Se já existe um psicólogo registrado, bloqueia o acesso à tela de cadastro
        try {
            $existe = $this->obterPsicologo()->existePsicologo();
        } catch (PDOException $e) {
            error_log('[Cadastro] Falha ao verificar a conta. SQLSTATE: ' . $e->getCode());
            $this->mostrarErro('register', 'account.load_failed', 503);
            return;
        }
        if ($existe) {
            Toast::add('account.exists');
            header('Location: /login');
            exit;
        }

        require_once __DIR__ . '/../../Views/Auth/register.php';
    }

    // Processa o primeiro cadastro do psicólogo
    public function registrar(): void {
        // Bloqueia caso já exista psicólogo cadastrado
        try {
            $existe = $this->obterPsicologo()->existePsicologo();
        } catch (PDOException $e) {
            error_log('[Cadastro] Falha ao verificar a conta. SQLSTATE: ' . $e->getCode());
            $this->mostrarErro('register', 'account.load_failed', 503);
            return;
        }
        if ($existe) {
            Toast::add('account.exists');
            header('Location: /login');
            exit;
        }

        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $senha = is_string($_POST['senha'] ?? null) ? $_POST['senha'] : '';
        if ($email === '' || $senha === '') {
            $this->mostrarErro('register', 'validation.required');
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->mostrarErro('register', 'validation.email');
            return;
        }
        if (mb_strlen($senha, 'UTF-8') < 8) {
            $this->mostrarErro('register', 'validation.password_length');
            return;
        }
        if (isset($_POST['confirmar_senha']) && (!is_string($_POST['confirmar_senha']) || $_POST['confirmar_senha'] !== $senha)) {
            $this->mostrarErro('register', 'validation.password_match');
            return;
        }
        try {
            $sucesso = $this->obterPsicologo()->criarPsicologo($email, $senha);
        } catch (PDOException $e) {
            error_log('[Cadastro] Falha ao salvar a conta. SQLSTATE: ' . $e->getCode());
            $this->mostrarErro('register', 'account.failed', 500);
            return;
        }
        if (!$sucesso) {
            $this->mostrarErro('register', 'account.failed', 409);
            return;
        }
        Toast::add('account.created');
        header('Location: /login', true, 303);
        exit;
    }

    // Visualizar perfil do psicólogo logado
    public function perfil(): void {
        Auth::verificar();
        $id = $_SESSION['psicologo_id'] ?? null;
        $perfil = $id ? $this->obterPsicologo()->visualizarPsicologo($id) : null;
        require_once __DIR__ . '/../../Views/Perfil/perfil-show.php';
    }

    // Encerra a sessão
    public function logout(): void {
        Auth::logout();
    }
}
