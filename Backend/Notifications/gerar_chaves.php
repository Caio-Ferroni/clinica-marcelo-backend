<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$diagnostico = in_array('--diagnostico', $argv, true);
$config = null;
$mostrarAmbiente = static function () use (&$config): void {
    fwrite(STDERR, 'PHP: ' . PHP_VERSION . PHP_EOL);
    fwrite(STDERR, 'Executável: ' . PHP_BINARY . PHP_EOL);
    fwrite(STDERR, 'php.ini: ' . (php_ini_loaded_file() ?: 'não carregado') . PHP_EOL);
    fwrite(STDERR, 'OpenSSL: ' . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'extensão não carregada') . PHP_EOL);
    fwrite(STDERR, 'Configuração OpenSSL: ' . ($config ?? 'padrão do sistema') . PHP_EOL);
};
try {
    if (PHP_VERSION_ID < 80200) throw new RuntimeException('É necessário PHP 8.2 ou superior.');
    if (!extension_loaded('openssl')) throw new RuntimeException('Habilite a extensão openssl no php.ini indicado abaixo.');
    foreach (array_slice($argv, 1) as $argumento) {
        if ($argumento === '--diagnostico') continue;
        if (!str_starts_with($argumento, '--config=') || $config !== null) throw new InvalidArgumentException('Use --diagnostico e/ou --config="caminho do openssl.cnf".');
        $config = substr($argumento, 9);
        if ($config === '') throw new InvalidArgumentException('Informe o caminho completo após --config=.');
    }
    $config ??= getenv('OPENSSL_CONF') ?: (getenv('SSLEAY_CONF') ?: null);
    if ($config === null && PHP_OS_FAMILY === 'Windows') {
        foreach ([dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf', dirname(PHP_BINARY, 2) . '/apache/conf/openssl.cnf'] as $candidato) {
            if (is_file($candidato) && is_readable($candidato)) { $config = $candidato; break; }
        }
    }
    if ($config !== null && (!is_file($config) || !is_readable($config))) throw new RuntimeException('O arquivo openssl.cnf informado não existe ou não pode ser lido. Corrija OPENSSL_CONF ou use --config=.');
    if (!in_array('prime256v1', openssl_get_curve_names(), true)) throw new RuntimeException('O OpenSSL deste PHP não oferece a curva P-256 (prime256v1).');
    $autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (!is_file($autoload)) throw new RuntimeException('Dependências ausentes. Execute composer install na pasta do projeto.');
    require_once $autoload;
    if (!class_exists(Minishlink\WebPush\VAPID::class)) throw new RuntimeException('A biblioteca minishlink/web-push não está instalada neste projeto. Execute composer install.');
    while (openssl_error_string() !== false) {}
    $opcoes = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1', 'private_key_bits' => 2048];
    if ($config !== null) $opcoes['config'] = $config;
    $chave = @openssl_pkey_new($opcoes);
    if ($chave === false) throw new RuntimeException('O OpenSSL não conseguiu gerar a chave P-256. Confira os detalhes abaixo e o arquivo openssl.cnf.');
    $detalhes = openssl_pkey_get_details($chave);
    $componentes = [];
    foreach (['x', 'y', 'd'] as $componente) {
        $valor = $detalhes['ec'][$componente] ?? null;
        if (!is_string($valor) || strlen($valor) < 1 || strlen($valor) > 32) throw new RuntimeException('O OpenSSL retornou uma chave P-256 incompleta.');
        $componentes[$componente] = str_pad($valor, 32, "\0", STR_PAD_LEFT);
    }
    $base64url = static fn(string $valor): string => rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    $keys = ['publicKey' => $base64url("\x04" . $componentes['x'] . $componentes['y']), 'privateKey' => $base64url($componentes['d'])];
    Minishlink\WebPush\VAPID::validate(['subject' => 'https://example.invalid', ...$keys]);
    if ($diagnostico) {
        $mostrarAmbiente();
        fwrite(STDOUT, "Diagnóstico OK: geração P-256 e formato VAPID válidos. Nenhuma chave foi exibida ou salva.\n");
        exit(0);
    }
    echo 'WEBPUSH_VAPID_PUBLIC_KEY=' . $keys['publicKey'] . PHP_EOL;
    echo 'WEBPUSH_VAPID_PRIVATE_KEY=' . $keys['privateKey'] . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Não foi possível gerar as chaves: ' . $error->getMessage() . PHP_EOL);
    $mostrarAmbiente();
    if (function_exists('openssl_error_string')) {
        while (($detalhe = openssl_error_string()) !== false) fwrite(STDERR, 'OpenSSL: ' . $detalhe . PHP_EOL);
    }
    fwrite(STDERR, "Confira os requisitos com: composer check-platform-reqs --no-dev\n");
    exit(1);
}
