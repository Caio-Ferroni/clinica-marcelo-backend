<?php
$consulta = is_array($consulta ?? null) ? $consulta : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};
$formatarHorario = static function ($valor) {
    if (empty($valor)) return '';
    return substr((string) $valor, 0, 5);
};

$idConsulta = $consulta['id_consulta'] ?? '';
$nomePaciente = $consulta['paciente_nome'] ?? ($consulta['nome'] ?? 'Paciente não informado');
$idPaciente = $consulta['id_paciente'] ?? null;
$telefone = !empty($consulta['telefone']) ? $consulta['telefone'] : 'Não informado';
$cpf = !empty($consulta['cpf']) ? $consulta['cpf'] : 'Não informado';
$dataNascimento = !empty($consulta['data_nascimento']) ? $formatarData($consulta['data_nascimento']) : 'Não informada';
$dataConsulta = !empty($consulta['data']) ? $formatarData($consulta['data']) : 'Não informada';
$horarioInicio = !empty($consulta['horario_inicio']) ? $formatarHorario($consulta['horario_inicio']) : '-';
$horarioFim = !empty($consulta['horario_fim']) ? $formatarHorario($consulta['horario_fim']) : '-';
$horarioCompleto = ($horarioInicio !== '-' || $horarioFim !== '-') ? "{$horarioInicio} às {$horarioFim}" : '-';
$status = !empty($consulta['status']) ? $consulta['status'] : 'Pendente';
$relatorio = trim((string) ($consulta['relatorio'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Consulta | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body>
    <clinic-sidebar active="patients"></clinic-sidebar>

    <clinic-footer></clinic-footer>
    <script type="module" src="../../js/bootstrap.min.js"></script>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>