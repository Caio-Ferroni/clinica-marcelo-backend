<?php
$pacientes = is_array($pacientes ?? null) ? $pacientes : [];
$consultas = is_array($consultas ?? null) ? $consultas : [];
$tarefas = is_array($tarefas ?? null) ? $tarefas : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    $valor = substr((string) $valor, 0, 10);
    $data = preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor) ? DateTimeImmutable::createFromFormat('!Y-m-d', $valor) : false;
    return $data && $data->format('Y-m-d') === $valor ? $data->format('d/m/Y') : '';
};
$agora = $agora ?? new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo'));
$hoje = $agora->format('Y-m-d');
$horaAtual = $agora->format('H:i:s');
$concluida = static fn($c) => in_array(mb_strtolower(trim((string) ($c['status'] ?? ''))), ['concluído', 'concluída', 'realizado', 'realizada'], true);
$consultasFuturas = array_filter($consultas, static function ($c) use ($hoje, $horaAtual) {
    return ($c['status'] ?? '') === 'Pendente' && !empty($c['data'])
        && ($c['data'] > $hoje || ($c['data'] === $hoje && ($c['horario_inicio'] ?? '') >= $horaAtual));
});
usort($consultasFuturas, static fn($a, $b) => strcmp(($a['data'] ?? '') . ' ' . ($a['horario_inicio'] ?? ''), ($b['data'] ?? '') . ' ' . ($b['horario_inicio'] ?? '')));
$proximaConsulta = $consultasFuturas[0] ?? null;
$mesesNomes = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
$dadosGrafico = [];
$mesBase = $agora->modify('first day of this month');
for ($i = 5; $i >= 0; $i--) {
    $mes = $mesBase->modify('-' . $i . ' months');
    $mesConsultas = array_filter($consultas, static fn($c) => substr((string) ($c['data'] ?? ''), 0, 7) === $mes->format('Y-m'));
    $realizadas = count(array_filter($mesConsultas, $concluida));
    $dadosGrafico[] = ['label' => $mesesNomes[(int) $mes->format('n')], 'outras' => count($mesConsultas) - $realizadas, 'realizadas' => $realizadas, 'total' => count($mesConsultas)];
}
$pacientesNovos = $pacientes;
usort($pacientesNovos, static fn($a, $b) => strcmp((string) ($b['criado_em'] ?? ''), (string) ($a['criado_em'] ?? '')) ?: ((int) $b['id_paciente'] <=> (int) $a['id_paciente']));
$pacientesNovos = array_slice($pacientesNovos, 0, 4);
$totalConsultasCount = count($consultas);
$realizadasCount = count(array_filter($consultas, $concluida));
$percRealizadas = $totalConsultasCount > 0 ? (int) round($realizadasCount / $totalConsultasCount * 100) : 0;
$percNaoRealizadas = $totalConsultasCount > 0 ? 100 - $percRealizadas : 0;
$tarefasPendentes = array_filter($tarefas, static fn($t) => ($t['status'] ?? 'Pendente') === 'Pendente');
$tarefasExibidas = array_slice($tarefasPendentes, 0, 4);
$agendaHoje = array_values(array_filter($consultas, static fn($c) => ($c['data'] ?? '') === $hoje));
usort($agendaHoje, static fn($a, $b) => strcmp((string) ($a['horario_inicio'] ?? ''), (string) ($b['horario_inicio'] ?? '')));
$agendaHoje = array_slice($agendaHoje, 0, 5);
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>">
    <script src="/js/chart.min.js"></script>
    <script src="/js/layout.js?v=<?= filemtime(__DIR__ . '/../../js/layout.js') ?>" defer></script>
</head>
<body class="dashboard-page">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="home"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Dashboard</h1>
                    </header>


                    <section aria-labelledby="proxima-heading" class="mb-7">
                        <p id="proxima-heading" class="section-label text-sm fw-medium mb-3" style="color: var(--color-text-muted);">Próxima consulta</p>
                        <div class="border rounded-4 shadow-sm p-6" style="background: var(--color-surface);">
                            <div class="row align-items-center g-4 text-center">
                                <div class="col-12 md:col-6 border-end-md" style="--bs-border-color: var(--color-border);">
                                    <?php if ($proximaConsulta): ?>
                                        <p class="text-xs mb-1" style="color: var(--color-text-muted);">Paciente</p>
                                        <h2 class="text-lg fw-semibold mb-0">
                                            <?php if (!empty($proximaConsulta['id_paciente'])): ?>
                                                <a href="/pacientes/<?= $escape($proximaConsulta['id_paciente']) ?>" class="text-decoration-none" style="color: var(--color-dark-blue);">
                                                    <?= $escape($proximaConsulta['paciente_nome'] ?? $proximaConsulta['nome'] ?? 'Paciente') ?>
                                                </a>
                                            <?php else: ?>
                                                <?= $escape($proximaConsulta['paciente_nome'] ?? $proximaConsulta['nome'] ?? 'Paciente') ?>
                                            <?php endif; ?>
                                        </h2>
                                    <?php else: ?>
                                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">Nenhuma consulta agendada</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 md:col-6">
                                    <?php if ($proximaConsulta && !empty($proximaConsulta['data'])): ?>
                                        <p class="text-xs mb-1" style="color: var(--color-text-muted);">Data e Horário</p>
                                        <p class="text-lg fw-semibold mb-0" style="color: var(--color-dark-blue);">
                                            <?= $formatarData($proximaConsulta['data']) ?> às <?= substr((string) ($proximaConsulta['horario_inicio'] ?? '00:00'), 0, 5) ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">-</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">


                    <section aria-labelledby="atalhos-heading" class="mb-7">
                        <p id="atalhos-heading" class="section-label text-sm fw-medium mb-3" style="color: var(--color-text-muted);">Atalhos</p>
                        <div class="row g-5">
                            <div class="col-12 md:col-6">
                                <a href="/pacientes/criar" class="border rounded-4 shadow-sm p-6 text-center text-decoration-none d-flex flex-column justify-content-center align-items-center h-100" style="background: var(--color-surface); color: var(--color-dark-blue); min-height: 110px;">
                                    <i class="bi bi-person-plus text-2xl mb-2" style="color: var(--color-dark-blue);" aria-hidden="true"></i>
                                    <span class="text-md fw-semibold">Adicionar Paciente</span>
                                </a>
                            </div>
                            <div class="col-12 md:col-6">
                                <a href="/consultas" class="border rounded-4 shadow-sm p-6 text-center text-decoration-none d-flex flex-column justify-content-center align-items-center h-100" style="background: var(--color-surface); color: var(--color-dark-blue); min-height: 110px;">
                                    <i class="bi bi-calendar-check text-2xl mb-2" style="color: var(--color-dark-blue);" aria-hidden="true"></i>
                                    <span class="text-md fw-semibold">Revisar Consultas</span>
                                </a>
                            </div>
                        </div>
                    </section>

                    <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">


                    <section aria-labelledby="pacientes-mes-heading" class="mb-7">
                        <p id="pacientes-mes-heading" class="section-label text-sm fw-medium mb-3" style="color: var(--color-text-muted);">Consultas por mês</p>
                        <div class="border rounded-4 shadow-sm p-7 md:p-8" style="background: var(--color-surface);">
                            <div style="height: 250px; position: relative;">
                                <canvas id="chartPacientesMes"></canvas>
                            </div>
                        </div>
                    </section>

                    <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">


                    <div class="row g-7 dashboard-stack mb-7">
                        <div class="col-12 md:col-6">
                            <section aria-labelledby="novos-heading" class="h-100">
                                <p id="novos-heading" class="section-label text-sm fw-medium mb-3" style="color: var(--color-text-muted);">Pacientes recentes</p>
                                <div class="border rounded-4 shadow-sm p-6 h-100 d-flex flex-column" style="background: var(--color-surface);">
                                    <?php if (empty($pacientesNovos)): ?>
                                        <p class="text-sm mb-0 text-center my-auto" style="color: var(--color-text-muted);">Nenhum paciente cadastrado recentemente.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                                            <?php foreach ($pacientesNovos as $p): ?>
                                                <li class="d-flex align-items-center justify-content-between gap-3">
                                                    <a href="/pacientes/<?= $escape($p['id_paciente']) ?>" class="border rounded-3 px-5 py-3 flex-grow-1 text-sm text-decoration-none" style="background: var(--color-field-background); color: inherit;">
                                                        <?= $escape($p['nome']) ?>
                                                    </a>
                                                    <small class="text-xs text-end flex-shrink-0" style="color: var(--color-text-muted);">
                                                        <?= $escape($p['status'] ?? 'Ativo') ?>
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>
                        <div class="col-12 md:col-6">
                            <section aria-labelledby="consultas-heading" class="h-100">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <p id="consultas-heading" class="section-label text-sm fw-medium mb-0" style="color: var(--color-text-muted);">Consultas</p>
                                    <small class="text-xs" style="color: var(--color-text-muted);">Visão geral</small>
                                </div>
                                <div class="border rounded-4 shadow-sm p-6 h-100 d-flex flex-column justify-content-center" style="background: var(--color-surface);">
                                    <div class="d-flex align-items-center justify-content-around flex-wrap gap-5 py-3">
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 12px; height: 12px; background: #102d5b;"></span>
                                                <span class="text-sm">Consultas realizadas: <strong><?= $totalConsultasCount > 0 ? $percRealizadas . '%' : '—' ?></strong></span>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="d-inline-block rounded-circle flex-shrink-0" style="width: 12px; height: 12px; background: #a8ddf5;"></span>
                                                <span class="text-sm">Consultas pendentes/outras: <strong><?= $percNaoRealizadas ?>%</strong></span>
                                            </div>
                                        </div>
                                        <div style="width: 120px; height: 120px; position: relative;">
                                            <canvas id="chartConsultasStatus"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">


                    <div class="row g-7 dashboard-stack mb-7">
                        <div class="col-12 md:col-6">
                            <section aria-labelledby="tarefas-heading" class="h-100">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <p id="tarefas-heading" class="section-label text-sm fw-medium mb-0" style="color: var(--color-text-muted);">Tarefas</p>
                                    <a href="/tarefas" class="text-xs text-decoration-none" style="color: var(--color-dark-blue);">Ver todas &rarr;</a>
                                </div>
                                <div class="border rounded-4 shadow-sm p-6 h-100" style="background: var(--color-surface);">
                                    <?php if (empty($tarefasExibidas)): ?>
                                        <p class="text-sm mb-0 text-center py-5" style="color: var(--color-text-muted);">Nenhuma tarefa pendente.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                                            <?php foreach ($tarefasExibidas as $t): ?>
                                                <li class="d-flex align-items-center justify-content-between gap-3 border rounded-3 p-4" style="background: var(--color-field-background);">
                                                    <span class="text-sm fw-medium text-truncate"><?= $escape($t['titulo']) ?></span>
                                                    <a href="/tarefas/<?= $escape($t['id_tarefa']) ?>/editar" class="btn btn-sm p-1 text-muted" aria-label="Editar tarefa">
                                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>
                        <div class="col-12 md:col-6">
                            <section aria-labelledby="agenda-heading" class="h-100">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                                    <p id="agenda-heading" class="section-label text-sm fw-medium mb-0" style="color: var(--color-text-muted);">Agenda hoje</p>
                                    <a href="/consultas" class="text-xs text-decoration-none" style="color: var(--color-dark-blue);">Ver consultas &rarr;</a>
                                </div>
                                <div class="border rounded-4 shadow-sm p-6 h-100" style="background: var(--color-surface);">
                                    <?php if (empty($agendaHoje)): ?>
                                        <p class="text-sm mb-0 text-center py-5" style="color: var(--color-text-muted);">Nenhuma consulta na agenda hoje.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
                                            <?php foreach ($agendaHoje as $c): ?>
                                                <li class="d-flex align-items-center justify-content-between gap-3 border rounded-3 p-4" style="background: var(--color-field-background);">
                                                    <span class="text-sm fw-medium text-truncate">
                                                        <?= $escape($c['paciente_nome'] ?? $c['nome'] ?? 'Consulta') ?>
                                                    </span>
                                                    <span class="text-xs fw-semibold px-3 py-1 rounded-2 flex-shrink-0" style="background: var(--color-lighter-blue); color: var(--color-dark-blue);">
                                                        <?= substr((string) ($c['horario_inicio'] ?? '00:00'), 0, 5) ?>
                                                    </span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </section>
                        </div>
                    </div>

                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js não encontrado.');
                return;
            }
            const ctxPacientes = document.getElementById('chartPacientesMes');
            if (ctxPacientes) {
                new Chart(ctxPacientes, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode(array_column($dadosGrafico, 'label')) ?>,
                        datasets: [
                            {
                                label: 'Outras consultas',
                                data: <?= json_encode(array_column($dadosGrafico, 'outras')) ?>,
                                backgroundColor: '#102d5b',
                                borderRadius: { bottomLeft: 4, bottomRight: 4 },
                                stack: 'pacientes'
                            },
                            {
                                label: 'Consultas concluídas',
                                data: <?= json_encode(array_column($dadosGrafico, 'realizadas')) ?>,
                                backgroundColor: '#a8ddf5',
                                borderRadius: { topLeft: 6, topRight: 6 },
                                stack: 'pacientes'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    boxWidth: 14,
                                    boxHeight: 14,
                                    color: '#61738a',
                                    font: { size: 12, family: 'inherit' }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: (item) => `${item.dataset.label}: ${item.parsed.y} consulta(s)`
                                }
                            }
                        },
                        scales: {
                            x: {
                                stacked: true,
                                grid: { display: false },
                                ticks: { color: '#61738a', font: { size: 12, weight: '600' } },
                                border: { color: '#dce6ed' }
                            },
                            y: {
                                stacked: true,
                                beginAtZero: true,
                                grid: { color: '#f0f4f7' },
                                ticks: {
                                    stepSize: 1,
                                    precision: 0,
                                    color: '#61738a',
                                    font: { size: 11 }
                                },
                                border: { dash: [4, 4], color: '#dce6ed' }
                            }
                        }
                    }
                });
            }
            const ctxConsultas = document.getElementById('chartConsultasStatus');
            if (ctxConsultas) {
                new Chart(ctxConsultas, {
                    type: 'doughnut',
                    data: {
                        labels: ['Consultas realizadas', 'Consultas pendentes/outras'],
                        datasets: [{
                            data: [<?= $realizadasCount ?>, <?= $totalConsultasCount - $realizadasCount ?>],
                            backgroundColor: ['#102d5b', '#a8ddf5'],
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '70%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: (item) => `${item.label}: ${item.parsed} consulta(s)`
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
