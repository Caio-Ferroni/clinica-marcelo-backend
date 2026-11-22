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
    if (array_diff($arguments, ['--dry-run', '--help', '--diagnostico', '--teste'])) {
        fwrite(STDERR, "Uso: php Backend/Cron/notificar_consultas.php [--dry-run|--diagnostico|--teste]\n");
        exit(1);
    }
    if (in_array('--help', $arguments, true)) {
        echo "Uso: php Backend/Cron/notificar_consultas.php [--dry-run|--diagnostico|--teste]\n";
        echo "--dry-run: conta pares consulta/dispositivo elegíveis, sem enviar nem registrar tentativas.\n";
        echo "--diagnostico: relatório somente leitura de configuração, banco e últimas entregas.\n";
        echo "--teste: envia uma notificação de teste a cada dispositivo inscrito e informa o resultado.\n";
        exit(0);
    }
    $config = WebPushConfig::fromEnvironment();
    if (in_array('--diagnostico', $arguments, true)) {
        $db = null;
        try {
            $db = Database::getConnection(true);
        } catch (Throwable $error) {
            $db = null;
        }
        echo (new WebPushDiagnostics($config, $db))->report();
        exit(0);
    }
    if (!$config->enabled) {
        echo "Webpush desabilitado.\n";
        exit(0);
    }
    $config->assertReady();
    $db = Database::getConnection(true);
    if (in_array('--teste', $arguments, true)) {
        exit(WebPushTestSender::run(new WebPushRepository($db), new WebPushService($config), null, $config->timezone));
    }
    $reminder = new ConsultaReminder(new WebPushRepository($db), new WebPushService($config), null, $config->reminderSeconds, $config->timezone);
    $counts = $reminder->run(in_array('--dry-run', $arguments, true));
    echo json_encode($counts, JSON_THROW_ON_ERROR) . PHP_EOL;
    exit(($counts['temporary'] + $counts['permanent']) > 0 ? 2 : 0);
} catch (Throwable $error) {
    // A mensagem destas exceções é produzida pelo próprio módulo ou pelo PDO: não contém chaves nem endpoints.
    fwrite(STDERR, '[WebPush] Falha ao executar: ' . $error->getMessage() . PHP_EOL);
    fwrite(STDERR, "Verifique dependências, configuração e migração. Use --diagnostico para o relatório completo.\n");
    exit(1);
}
