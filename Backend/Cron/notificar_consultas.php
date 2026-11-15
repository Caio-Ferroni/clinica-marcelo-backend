<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__, 2);
require_once $root . '/vendor/autoload.php';
require_once $root . '/Backend/Config/Database.php';
require_once $root . '/Backend/Notifications/bootstrap.php';

try {
    Dotenv\Dotenv::createImmutable($root)->safeLoad();
    $arguments = array_slice($argv, 1);
    if (array_diff($arguments, ['--dry-run', '--help'])) {
        fwrite(STDERR, "Uso: php Backend/Cron/notificar_consultas.php [--dry-run]\n");
        exit(1);
    }
    if (in_array('--help', $arguments, true)) {
        echo "Uso: php Backend/Cron/notificar_consultas.php [--dry-run]\n";
        echo "--dry-run: conta pares consulta/dispositivo elegíveis, sem enviar nem registrar tentativas.\n";
        exit(0);
    }
    $config = WebPushConfig::fromEnvironment();
    if (!$config->enabled) {
        echo "Webpush desabilitado.\n";
        exit(0);
    }
    $config->assertReady();
    $db = Database::getConnection(true);
    $reminder = new ConsultaReminder(new WebPushRepository($db), new WebPushService($config));
    $counts = $reminder->run(in_array('--dry-run', $arguments, true));
    echo json_encode($counts, JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(($counts['temporary'] + $counts['permanent']) > 0 ? 2 : 0);
} catch (Throwable $error) {
    fwrite(STDERR, "[WebPush] Falha ao executar. Verifique dependências, configuração e migração.\n");
    exit(1);
}
