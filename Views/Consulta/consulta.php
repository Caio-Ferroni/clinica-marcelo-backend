<?php
$consultas = is_array($consultas ?? null) ? $consultas : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};
$formatarHorario = static function ($inicio, $fim = null) {
    if (empty($inicio)) return '';
    $horaInicio = substr((string) $inicio, 0, 5);
    if (!empty($fim)) {
        return $horaInicio . ' - ' . substr((string) $fim, 0, 5);
    }
    return $horaInicio;
};

$mesAtual = date('m');
$anoAtual = date('Y');
$consultasMes = 0;
$consultasRealizadas = 0;
$consultasCanceladas = 0;

foreach ($consultas as $c) {
    if (!empty($c['data'])) {
        $timestamp = strtotime((string) $c['data']);
        if ($timestamp !== false && date('m', $timestamp) === $mesAtual && date('Y', $timestamp) === $anoAtual) {
            $consultasMes++;
        }
    }
    $status = mb_strtolower(trim((string) ($c['status'] ?? '')));
    if (in_array($status, ['realizada', 'realizado', 'concluída', 'concluído'])) {
        $consultasRealizadas++;
    } elseif (in_array($status, ['cancelada', 'cancelado'])) {
        $consultasCanceladas++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultas | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body class="appointments-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="appointments"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Consultas</h1>
                    </header>
                    <div class="row g-5 mb-7">
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas esse mês</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_esse_mes"><?= $consultasMes ?> <?= $consultasMes === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas realizadas</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_realizadas"><?= $consultasRealizadas ?> <?= $consultasRealizadas === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas canceladas</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_canceladas"><?= $consultasCanceladas ?> <?= $consultasCanceladas === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <div class="border rounded-4 shadow-sm table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-consultas">
                            <caption class="visually-hidden">Lista de consultas</caption>
                            <thead style="border-bottom: 1px solid var(--color-border);">
                                <tr>
                                    <th scope="col" class="text-center text-sm fw-semibold">Paciente</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Horário</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Status</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Nascimento</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($consultas)): ?>
                                    <?php foreach ($consultas as $c): ?>
                                        <?php
                                        $nomePaciente = $c['paciente_nome'] ?? $c['nome'] ?? 'Paciente não informado';
                                        $idPaciente = $c['id_paciente'] ?? null;
                                        $horario = $formatarHorario($c['horario_inicio'] ?? '', $c['horario_fim'] ?? null);
                                        $dataConsulta = $formatarData($c['data'] ?? '');
                                        $horarioCompleto = $dataConsulta !== '' ? ($horario !== '' ? "{$dataConsulta} · {$horario}" : $dataConsulta) : ($horario !== '' ? $horario : '-');
                                        $nascimento = $formatarData($c['data_nascimento'] ?? '');
                                        ?>
                                        <tr data-id="<?= $escape($c['id_consulta'] ?? '') ?>">
                                            <td class="text-center text-sm align-middle" data-field="paciente">
                                                <?php if (!empty($idPaciente)): ?>
                                                    <a href="/pacientes/<?= $escape($idPaciente) ?>" class="text-decoration-none text-reset fw-medium">
                                                        <?= $escape($nomePaciente) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <?= $escape($nomePaciente) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center text-sm align-middle" data-field="horario"><?= $escape($horarioCompleto) ?></td>
                                            <td class="text-center text-sm align-middle" data-field="status"><?= $escape($c['status'] ?? 'Pendente') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="nascimento"><?= $escape($nascimento !== '' ? $nascimento : '-') ?></td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-sm p-2" href="/relatorios/<?= $escape($c['id_consulta'] ?? '') ?>" aria-label="Ver relatório">
                                                        <i class="bi bi-clipboard-fill" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
</body>
</html>
