<?php
declare(strict_types=1);

/**
 * Instala (ou remove) a tarefa do Windows que executa o agendador de lembretes a cada minuto.
 *
 * Usa o próprio PHP que executa este arquivo, o caminho real do projeto e um log em .local/,
 * para não depender de o operador montar a linha de comando do schtasks à mão.
 *
 * Uso:
 *   php scripts/instalar-agendador.php
 *   php scripts/instalar-agendador.php --remover
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
$taskName = 'ClinicaWebPush';
$cron = $root . '/Backend/Cron/notificar_consultas.php';
$wrapper = $root . '/.local/webpush-agendador.cmd';
$log = $root . '/.local/webpush-agendador.log';

function executar(string $command): array
{
    $output = [];
    $code = 0;
    exec($command . ' 2>&1', $output, $code);
    return [$code, $output];
}

if (!in_array('--remover', $argv, true) && !in_array('--help', $argv, true) && PHP_OS_FAMILY !== 'Windows') {
    fwrite(STDERR, "Este instalador é apenas para Windows.\n\n");
    fwrite(STDERR, "No Linux, acrescente ao cron do usuário que lê o .env:\n");
    fwrite(STDERR, "  * * * * * " . PHP_BINARY . ' ' . $cron . " >> " . $root . "/.local/webpush-agendador.log 2>&1\n");
    exit(1);
}

if (in_array('--help', $argv, true)) {
    echo "Uso: php scripts/instalar-agendador.php [--remover]\n";
    echo "Cria a tarefa \"" . $taskName . "\" executando o agendador a cada minuto, com log em .local/.\n";
    echo "--remover: exclui a tarefa e o script auxiliar.\n";
    exit(0);
}

if (in_array('--remover', $argv, true)) {
    [$code, $output] = executar('schtasks /delete /tn "' . $taskName . '" /f');
    echo $code === 0 ? "Tarefa removida.\n" : "Não foi possível remover a tarefa:\n" . implode("\n", $output) . "\n";
    if (is_file($wrapper)) {
        unlink($wrapper);
    }
    exit($code === 0 ? 0 : 1);
}

if (!is_file($cron)) {
    fwrite(STDERR, "Script do agendador não encontrado: {$cron}\n");
    exit(1);
}
if (!is_dir(dirname($log))) {
    mkdir(dirname($log), 0775, true);
}

// O wrapper existe para redirecionar a saída sem depender das aspas do schtasks.
$linha = '@echo off' . "\r\n"
    . '"' . PHP_BINARY . '" "' . $cron . '" >> "' . $log . '" 2>&1' . "\r\n";
if (file_put_contents($wrapper, $linha) === false) {
    fwrite(STDERR, "Não foi possível escrever {$wrapper}\n");
    exit(1);
}

[$code, $output] = executar('schtasks /create /tn "' . $taskName . '" /tr "' . $wrapper . '" /sc minute /mo 1 /f');
if ($code !== 0) {
    fwrite(STDERR, "Não foi possível criar a tarefa:\n" . implode("\n", $output) . "\n");
    fwrite(STDERR, "Tente executar o terminal como administrador ou criar a tarefa pelo Agendador de Tarefas.\n");
    exit(1);
}
echo "Tarefa \"" . $taskName . "\" criada (a cada 1 minuto).\n";

[$runCode, $runOutput] = executar('schtasks /run /tn "' . $taskName . '"');
echo $runCode === 0 ? "Execução de verificação disparada.\n" : "Aviso: não foi possível disparar agora:\n" . implode("\n", $runOutput) . "\n";

// Dá tempo do PHP iniciar e escrever no log.
for ($tentativa = 0; $tentativa < 10 && (!is_file($log) || filesize($log) === 0); $tentativa++) {
    usleep(500000);
}

echo "\n";
echo '  PHP:    ' . PHP_BINARY . "\n";
echo '  Script: ' . $cron . "\n";
echo '  Log:    ' . $log . "\n";
echo '  Tarefa: ' . $taskName . "\n\n";

if (!is_file($log) || filesize($log) === 0) {
    echo "A execução de verificação ainda não gravou nada no log. Rode: php " . $cron . " --dry-run\n";
    exit(0);
}

$conteudo = (string) file_get_contents($log);
$ultimas = array_slice(array_filter(explode("\n", trim($conteudo))), -5);
echo "Últimas linhas do log:\n";
foreach ($ultimas as $linha) {
    echo '  ' . trim($linha) . "\n";
}

if (str_contains($conteudo, 'Webpush desabilitado.')) {
    echo "\nATENÇÃO: o agendador executou, mas não encontrou WEBPUSH_ENABLED=true no .env\n";
    echo "no contexto da tarefa (usuário/permissão de leitura). Nesse caso ele encerra sem enviar.\n";
}
if (str_contains($conteudo, 'Falha ao executar')) {
    echo "\nATENÇÃO: o agendador executou e falhou. A causa está na linha acima; compare com\n";
    echo "o PHP usado aqui e rode --diagnostico com esse mesmo executável.\n";
}
