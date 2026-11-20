<?php
$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$valorData = substr((string) ($consulta['data'] ?? ''), 0, 10);
$data = DateTimeImmutable::createFromFormat('!Y-m-d', $valorData);
$dataFormatada = $data && $data->format('Y-m-d') === $valorData ? $data->format('d/m/Y') : '—';
$relatorio = (string) ($consulta['relatorio'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
    <title>Relatório da consulta | Clínica</title>
</head>
<body class="appointments-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="reports"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Relatório da consulta</h1>
                    </header>
                    <?php if (!empty($erro)): ?>
                        <p class="border rounded-3 p-5 mb-5" role="alert" data-toast-type="error"><?= $escapar($erro) ?></p>
                    <?php elseif ($consulta !== null): ?>
                        <section class="border rounded-4 shadow-sm p-7 md:p-9 mb-7" style="background: var(--color-surface);" aria-labelledby="report-patient">
                            <h2 id="report-patient" class="text-lg fw-semibold mb-5"><?= $escapar($consulta['paciente_nome'] ?? 'Paciente indisponível') ?></h2>
                            <dl class="row g-5 mb-7">
                                <div class="col-12 md:col-4">
                                    <dt class="text-sm mb-2">Data</dt>
                                    <dd class="text-sm mb-0"><?= $dataFormatada ?></dd>
                                </div>
                                <div class="col-12 md:col-4">
                                    <dt class="text-sm mb-2">Horário</dt>
                                    <dd class="text-sm mb-0"><?= $escapar(substr((string) $consulta['horario_inicio'], 0, 5)) ?></dd>
                                </div>
                                <div class="col-12 md:col-4">
                                    <dt class="text-sm mb-2">Status</dt>
                                    <dd class="text-sm mb-0"><?= $escapar($consulta['status']) ?></dd>
                                </div>
                            </dl>
                            <h3 class="text-md fw-semibold mb-5">Relatório</h3>
                            <?php if (trim($relatorio) !== ''): ?>
                                <p class="text-sm mb-0" data-field="relatorio" style="white-space: pre-wrap; overflow-wrap: anywhere;"><?= $escapar($relatorio) ?></p>
                            <?php else: ?>
                                <p class="text-sm mb-0">Esta consulta ainda não possui relatório.</p>
                            <?php endif; ?>
                        </section>
                    <?php endif; ?>
                    <a class="btn-outline text-decoration-none" href="/consultas">Voltar às consultas</a>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
