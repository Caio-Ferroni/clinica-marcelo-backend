<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
try {
    $keys = Minishlink\WebPush\VAPID::createVapidKeys();
    echo 'WEBPUSH_VAPID_PUBLIC_KEY=' . $keys['publicKey'] . PHP_EOL;
    echo 'WEBPUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, "Não foi possível gerar as chaves. Verifique OpenSSL e as dependências.\n");
    exit(1);
}
