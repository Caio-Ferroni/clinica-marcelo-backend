<?php
final class EnvioPaciente {
    private static function limpar(): void {
        Auth::iniciarSessao();
        $_SESSION['envios_paciente'] ??= [];
        foreach ($_SESSION['envios_paciente'] as $token => $envio) {
            if ($envio['expira'] < time()) unset($_SESSION['envios_paciente'][$token]);
        }
    }

    public static function valido($token, string $escopo): bool {
        self::limpar();
        return is_string($token) && ($_SESSION['envios_paciente'][$token]['escopo'] ?? null) === $escopo;
    }

    public static function token(string $escopo, $anterior = null): string {
        if (self::valido($anterior, $escopo) && self::resultado($anterior) === null) return $anterior;
        if (count($_SESSION['envios_paciente']) >= 30) array_shift($_SESSION['envios_paciente']);
        $token = bin2hex(random_bytes(32));
        $_SESSION['envios_paciente'][$token] = ['escopo' => $escopo, 'expira' => time() + 7200, 'id' => null];
        return $token;
    }

    public static function resultado(string $token): ?int {
        return $_SESSION['envios_paciente'][$token]['id'] ?? null;
    }

    public static function concluir(string $token, int $id): void {
        $_SESSION['envios_paciente'][$token]['id'] = $id;
    }
}
