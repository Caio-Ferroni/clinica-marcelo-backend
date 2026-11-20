<?php
require_once __DIR__ . '/../Support/Toast.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Auth {

    // Garante que a sessão PHP está ativa
    public static function iniciarSessao(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function gerarCodigo2FA(array $psicologo): bool {
        self::iniciarSessao();
        self::limpar2FA();

        $codigo = (string) random_int(100000, 999999);

        if (!self::enviarEmailCodigo($psicologo['email'], $codigo)) {
            return false;
        }

        $_SESSION['2fa_codigo']          = $codigo;
        $_SESSION['2fa_expira']          = time() + (10 * 60);
        $_SESSION['2fa_psicologo_id']    = $psicologo['id_psicologo'];
        $_SESSION['2fa_psicologo_email'] = $psicologo['email'];

        return true;
    }

    public static function csrfToken(): string {
        self::iniciarSessao();
        $_SESSION['form_csrf'] ??= bin2hex(random_bytes(32));
        return $_SESSION['form_csrf'];
    }

    public static function validarCsrf($token): bool {
        self::iniciarSessao();
        return is_string($token) && is_string($_SESSION['form_csrf'] ?? null)
            && hash_equals($_SESSION['form_csrf'], $token);
    }

    // Valida o código 2FA digitado contra o que está na memória da sessão
    public static function validarCodigo2FA(string $codigoDigitado): bool {
        self::iniciarSessao();

        if (empty($_SESSION['2fa_codigo']) || empty($_SESSION['2fa_expira'])) {
            return false;
        }

        // Verifica se o código expirou
        if (time() > $_SESSION['2fa_expira']) {
            self::limpar2FA();
            return false;
        }

        // Compara os códigos
        if ($_SESSION['2fa_codigo'] === trim($codigoDigitado)) {
            // Autenticação concluída com sucesso!
            $_SESSION['autenticado']     = true;
            $_SESSION['psicologo_id']    = $_SESSION['2fa_psicologo_id'];
            $_SESSION['psicologo_email'] = $_SESSION['2fa_psicologo_email'];

            self::limpar2FA();
            return true;
        }

        return false;
    }

    // Remove os dados temporários do 2FA da sessão
    private static function limpar2FA(): void {
        unset(
            $_SESSION['2fa_codigo'],
            $_SESSION['2fa_expira'],
            $_SESSION['2fa_psicologo_id'],
            $_SESSION['2fa_psicologo_email']
        );
    }

    public static function enviarEmailCodigo(string $para, string $codigo): bool {
        $config = $_ENV + $_SERVER;
        foreach (['SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'MAIL_FROM_ADDRESS'] as $campo) {
            if (!isset($config[$campo]) || !is_string($config[$campo]) || trim($config[$campo]) === '') {
                error_log('[2FA] Configuração de e-mail ausente: ' . $campo);
                return false;
            }
        }

        $porta = filter_var($config['SMTP_PORT'] ?? '587', FILTER_VALIDATE_INT);
        $criptografia = strtolower(trim($config['SMTP_ENCRYPTION'] ?? 'tls'));
        if (!in_array([$porta, $criptografia], [[587, 'tls'], [465, 'ssl']], true)) {
            error_log('[2FA] Use SMTP_PORT=587 com tls ou SMTP_PORT=465 com ssl.');
            return false;
        }

        if (!extension_loaded('openssl')) {
            error_log('[2FA] A extensão OpenSSL precisa estar habilitada no PHP.');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = trim($config['SMTP_HOST']);
            $mail->Port = $porta;
            $mail->SMTPAuth = true;
            $mail->Username = trim($config['SMTP_USERNAME']);
            $mail->Password = $config['SMTP_PASSWORD'];
            $mail->SMTPSecure = $criptografia === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Timeout = 15;
            $mail->getSMTPInstance()->Timelimit = 15;
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom(trim($config['MAIL_FROM_ADDRESS']), $config['MAIL_FROM_NAME'] ?? 'Clínica');
            $mail->addAddress($para);
            $mail->Subject = 'Código de Verificação - Clínica Psicológica';
            $mail->Body = "Olá,\n\nSeu código de verificação é: {$codigo}\n\nEste código é válido por 10 minutos.\nSe você não solicitou este acesso, ignore este e-mail.";

            return $mail->send();
        } catch (Exception $erro) {
            error_log('[2FA] Não foi possível concluir o envio SMTP.');
            return false;
        }
    }

    // Verifica se o psicólogo está completamente autenticado
    public static function estaAutenticado(): bool {
        self::iniciarSessao();
        return !empty($_SESSION['autenticado']) && $_SESSION['autenticado'] === true;
    }

    // Verifica se está aguardando o código 2FA
    public static function aguardando2FA(): bool {
        self::iniciarSessao();
        return !empty($_SESSION['2fa_codigo']) && time() <= ($_SESSION['2fa_expira'] ?? 0);
    }

    // Middleware de proteção de rotas privadas
    public static function verificar(): void {
        if (!self::estaAutenticado()) {
            Toast::add('auth.required');
            header('Location: /login');
            exit;
        }
    }

    // Encerra a sessão e desloga o usuário
    public static function logout(): void {
        self::iniciarSessao();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();
        session_id('');
        session_start();
        session_regenerate_id(true);
        Toast::add('auth.logout');
        header('Location: /login');
        exit;
    }
}

