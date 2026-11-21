<?php
$consultas = $consultas ?? [];
$escapar = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor): string {
    $valor = substr((string) $valor, 0, 10);
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);
    return $data && $data->format('Y-m-d') === $valor ? $data->format('d/m/Y') : '—';
};
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
                    <?php if (!empty($erro)): ?>
                        <p class="border rounded-3 p-5 mb-5" role="alert" data-toast-type="<?= $toastType ?? 'error' ?>"><?= $escapar($erro) ?></p>
                    <?php endif; ?>
                    <p class="border rounded-3 p-5 mb-5" data-status-feedback role="status" hidden></p>
                    <div class="row g-5 mb-7">
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas esse mês</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_esse_mes"><?= $escapar($indicadores['mes'] ?? '—') ?> <?= ($indicadores['mes'] ?? null) === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas realizadas</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_realizadas"><?= $escapar($indicadores['realizadas'] ?? '—') ?> <?= ($indicadores['realizadas'] ?? null) === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Consultas canceladas</p>
                                <p class="text-lg fw-semibold mb-0" data-field="consultas_canceladas"><?= $escapar($indicadores['canceladas'] ?? '—') ?> <?= ($indicadores['canceladas'] ?? null) === 1 ? 'Consulta' : 'Consultas' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <form method="post" action="/consultas/gerar" data-generate-consultations data-toast-validate class="mb-5">
                        <input type="hidden" name="csrf_token" value="<?= $escapar($csrf) ?>">
                        <button class="btn-outline px-7 py-4 d-inline-flex align-items-center gap-3" type="submit">
                            <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                            <span>Gerar consultas do mês</span>
                        </button>
                    </form>
                    <div class="border rounded-4 shadow-sm table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-consultas">
                            <caption class="visually-hidden">Lista de consultas</caption>
                            <thead style="border-bottom: 1px solid var(--color-border);">
                                <tr>
                                    <th scope="col" class="text-center text-sm fw-semibold">Paciente</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Data e horário</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Status</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Relatório</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($consultas as $consulta): ?>
                                    <?php $id = (int) $consulta['id_consulta']; ?>
                                    <tr data-id="<?= $id ?>">
                                        <td class="text-center text-sm align-middle" data-field="paciente"><?= $escapar($consulta['paciente_nome'] ?? 'Paciente indisponível') ?></td>
                                        <td class="text-center text-sm align-middle" data-field="horario">
                                            <span class="d-block"><?= $formatarData($consulta['data']) ?></span>
                                            <?= $escapar(substr((string) $consulta['horario_inicio'], 0, 5)) ?>
                                        </td>
                                        <td class="text-center text-sm align-middle" data-field="status">
                                            <form method="post" action="/consultas/<?= $id ?>/status" data-status-form data-id="<?= $id ?>" data-toast-validate class="d-flex flex-column align-items-stretch gap-3 m-0" style="min-width: 165px;">
                                                <input type="hidden" name="csrf_token" value="<?= $escapar($csrf) ?>">
                                                <label class="visually-hidden" for="consulta-status-<?= $id ?>">Status da consulta de <?= $escapar($consulta['paciente_nome'] ?? 'paciente indisponível') ?> em <?= $formatarData($consulta['data']) ?></label>
                                                <select class="form-control" id="consulta-status-<?= $id ?>" name="status" data-saved-value="<?= $escapar(in_array($consulta['status'], $statusPermitidos, true) ? $consulta['status'] : '') ?>" required>
                                                    <?php if (!in_array($consulta['status'], $statusPermitidos, true)): ?>
                                                        <option value="" disabled selected><?= $escapar($consulta['status'] ?? 'Selecione o status') ?></option>
                                                    <?php endif; ?>
                                                    <?php foreach ($statusPermitidos as $status): ?>
                                                        <option value="<?= $escapar($status) ?>" <?= $consulta['status'] === $status ? 'selected' : '' ?>><?= $escapar($status) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <noscript><button class="btn-outline text-sm" type="submit">Salvar status</button></noscript>
                                            </form>
                                        </td>
                                        <td class="text-center text-sm align-middle" data-field="relatorio">
                                            <?php $podeEditar = Consulta::podeEditarRelatorio($consulta); ?>
                                            <div class="d-flex justify-content-center gap-3">
                                                <a class="btn btn-sm p-2<?= $podeEditar ? '' : ' disabled' ?>" data-consulta-edit data-edit-url="/consultas/<?= $id ?>/editar" aria-label="Editar relatório" title="<?= $podeEditar ? 'Editar relatório' : $escapar(Toast::message('report.not_completed')) ?>" <?= $podeEditar ? 'href="/consultas/' . $id . '/editar"' : 'role="link" aria-disabled="true" tabindex="-1"' ?>>
                                                    <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                                </a>
                                                <a class="btn btn-sm p-2" href="/consultas/<?= $id ?>" aria-label="Visualizar consulta" title="Visualizar consulta">
                                                    <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($consultas === []): ?>
                                    <tr data-empty-row><td colspan="4" class="text-center p-7"><?= $indicadores === null ? 'Não foi possível carregar a lista.' : 'Nenhuma consulta cadastrada.' ?></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
    <script src="/js/consultas.js?v=<?= filemtime(__DIR__ . '/../../js/consultas.js') ?>" defer></script>
</body>
</html>
