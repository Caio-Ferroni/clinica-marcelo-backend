<?php
// Backend/Auth/Auth.php

class Auth {

    // Garante que a sessão PHP está ativa
    public static function iniciarSessao(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Gera o código 2FA de 6 dígitos, salva na sessão (memória) e dispara por e-mail
    public static function gerarCodigo2FA(array $psicologo): string {
        self::iniciarSessao();

        $codigo = (string) random_int(100000, 999999);

        // Armazena temporariamente na sessão por 10 minutos
        $_SESSION['2fa_codigo']          = $codigo;
        $_SESSION['2fa_expira']          = time() + (10 * 60);
        $_SESSION['2fa_psicologo_id']    = $psicologo['id_psicologo'];
        $_SESSION['2fa_psicologo_email'] = $psicologo['email'];

        // Envia por e-mail
        self::enviarEmailCodigo($psicologo['email'], $codigo);

        return $codigo;
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

    // Envia o e-mail contendo o código de confirmação
    public static function enviarEmailCodigo(string $para, string $codigo): bool {
        $assunto = "Código de Verificação - Clínica Psicológica";
        $mensagem = "Olá,\n\nSeu código de verificação para acesso ao dashboard é: {$codigo}\n\n";
        $mensagem .= "Este código é válido por 10 minutos.\nSe você não solicitou este acesso, ignore este e-mail.";
        
        $headers = "From: no-reply@clinicamarcelo.com\r\n";
        $headers .= "Reply-To: no-reply@clinicamarcelo.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        // Salva também no log de erro do servidor para facilitar testes em ambiente de desenvolvimento local
        error_log("[2FA Clínica Marcelo] Código para {$para}: {$codigo}");

        return @mail($para, $assunto, $mensagem, $headers);
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
        header('Location: /login');
        exit;
    }
}

