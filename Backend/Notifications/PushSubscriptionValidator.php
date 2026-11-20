<?php
declare(strict_types=1);

final class PushSubscriptionValidator
{
    public static function endpoint(mixed $endpoint): string
    {
        if (!is_string($endpoint) || strlen($endpoint) > 4096 || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Endpoint inválido.');
        }
        $url = parse_url($endpoint);
        $host = strtolower($url['host'] ?? '');
        $allowed = in_array($host, ['fcm.googleapis.com', 'updates.push.services.mozilla.com'], true)
            || (bool) preg_match('/^(?:[a-z0-9-]+\.)+push\.apple\.com$/D', $host)
            || (bool) preg_match('/^(?:[a-z0-9-]+\.)+notify\.windows\.com$/D', $host);
        if (!$allowed || ($url['scheme'] ?? '') !== 'https'
            || isset($url['user']) || isset($url['fragment']) || (($url['port'] ?? 443) !== 443)) {
            throw new InvalidArgumentException('Serviço de push não permitido.');
        }
        return $endpoint;
    }

    public static function validate(array $input): array
    {
        $endpoint = self::endpoint($input['endpoint'] ?? null);
        $public = self::decode($input['keys']['p256dh'] ?? null, 65);
        $auth = self::decode($input['keys']['auth'] ?? null, 16);
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode(
            hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $public
        ), 64, "\n") . "-----END PUBLIC KEY-----\n";
        if ($public[0] !== "\x04" || openssl_pkey_get_public($pem) === false) {
            throw new InvalidArgumentException('Chave de inscrição inválida.');
        }
        $expiration = $input['expirationTime'] ?? null;
        if ($expiration !== null && ((!is_int($expiration) && !is_float($expiration))
            || !is_finite((float) $expiration) || $expiration <= microtime(true) * 1000
            || $expiration > 253402300799000)) {
            throw new InvalidArgumentException('Validade da inscrição inválida.');
        }
        return [
            'endpoint' => $endpoint,
            'p256dh' => self::encode($public),
            'auth' => self::encode($auth),
            'expires_at' => $expiration === null ? null : gmdate('Y-m-d H:i:s', (int) floor($expiration / 1000)),
        ];
    }

    private static function decode(mixed $key, int $length): string
    {
        if (!is_string($key) || strlen($key) > 128 || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $key)) {
            throw new InvalidArgumentException('Chave de inscrição inválida.');
        }
        $decoded = base64_decode(strtr($key, '-_', '+/'), true);
        if ($decoded === false || strlen($decoded) !== $length || self::encode($decoded) !== rtrim($key, '=')) {
            throw new InvalidArgumentException('Chave de inscrição inválida.');
        }
        return $decoded;
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
