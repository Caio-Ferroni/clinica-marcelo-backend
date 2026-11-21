<?php
declare(strict_types=1);

final class WebPushConfig
{
    public const TIMEZONE = 'America/Sao_Paulo';

    public function __construct(
        public readonly bool $enabled = false,
        public readonly string $publicKey = '',
        public readonly string $privateKey = '',
        public readonly string $subject = '',
        public readonly int $reminderSeconds = 600
    ) {}

    public static function fromEnvironment(): self
    {
        $env = array_merge(getenv() ?: [], $_SERVER, $_ENV);
        $minutes = filter_var($env['WEBPUSH_REMINDER_MINUTES'] ?? '10', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 1440],
        ]);
        return new self(
            filter_var($env['WEBPUSH_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN),
            trim((string) ($env['WEBPUSH_VAPID_PUBLIC_KEY'] ?? '')),
            trim((string) ($env['WEBPUSH_VAPID_PRIVATE_KEY'] ?? '')),
            trim((string) ($env['WEBPUSH_VAPID_SUBJECT'] ?? '')),
            ($minutes === false ? 10 : (int) $minutes) * 60
        );
    }

    public function assertReady(): void
    {
        foreach (['curl', 'mbstring', 'openssl', 'pdo_mysql'] as $extension) {
            if (!extension_loaded($extension)) {
                throw new RuntimeException('Extensão PHP necessária: ' . $extension);
            }
        }
        if ($this->publicKey === '' || $this->privateKey === '' || $this->subject === '') {
            throw new RuntimeException('Configure as chaves e o contato VAPID.');
        }
        if (!preg_match('~^(mailto:[^\s@]+@[^\s@]+|https://[^\s]+)$~D', $this->subject)) {
            throw new RuntimeException('Contato VAPID inválido.');
        }
        $keys = \Minishlink\WebPush\VAPID::validate([
            'subject' => $this->subject,
            'publicKey' => $this->publicKey,
            'privateKey' => $this->privateKey,
        ]);
        $der = hex2bin('30310201010420') . $keys['privateKey'] . hex2bin('a00a06082a8648ce3d030107');
        $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n")
            . "-----END EC PRIVATE KEY-----\n";
        $private = openssl_pkey_get_private($pem);
        $details = $private === false ? [] : openssl_pkey_get_details($private);
        $ec = $details['ec'] ?? [];
        $public = "\x04" . str_pad($ec['x'] ?? '', 32, "\0", STR_PAD_LEFT)
            . str_pad($ec['y'] ?? '', 32, "\0", STR_PAD_LEFT);
        if ($private === false || !hash_equals($keys['publicKey'], $public)) {
            throw new RuntimeException('As chaves VAPID não formam um par válido.');
        }
    }
}
