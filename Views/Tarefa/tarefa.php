<?php
$tarefas = is_array($tarefas ?? null) ? $tarefas : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};

$totalTarefas = count($tarefas);
$tarefasPendentes = 0;
$tarefasConcluidas = 0;

foreach ($tarefas as $t) {
    $status = mb_strtolower(trim((string) ($t['status'] ?? 'pendente')));
    if (in_array($status, ['concluída', 'concluida', 'concluído', 'concluido', 'finalizada'])) {
        $tarefasConcluidas++;
    } else {
        $tarefasPendentes++;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarefas | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>">
    <script src="/js/layout.js?v=<?= filemtime(__DIR__ . '/../../js/layout.js') ?>" defer></script>
</head>
<body class="tasks-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="tasks"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Tarefas</h1>
                    </header>
                    <div class="row g-5 mb-7">
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Tarefas totais</p>
                                <p class="text-lg fw-semibold mb-0" data-field="tarefas_totais"><?= $totalTarefas ?> <?= $totalTarefas === 1 ? 'Tarefa' : 'Tarefas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Tarefas pendentes</p>
                                <p class="text-lg fw-semibold mb-0" data-field="tarefas_pendentes"><?= $tarefasPendentes ?> <?= $tarefasPendentes === 1 ? 'Tarefa' : 'Tarefas' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Tarefas concluídas</p>
                                <p class="text-lg fw-semibold mb-0" data-field="tarefas_concluidas"><?= $tarefasConcluidas ?> <?= $tarefasConcluidas === 1 ? 'Tarefa' : 'Tarefas' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <div class="mb-5">
                        <a class="btn-outline px-7 py-4 text-decoration-none d-inline-flex align-items-center gap-3" href="/tarefas/criar">
                            <i class="bi bi-plus-circle" aria-hidden="true"></i>
                            Adicionar tarefa
                        </a>
                    </div>
                    <div class="border rounded-4 shadow-sm table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-tarefas">
                            <caption class="visually-hidden">Lista de tarefas</caption>
                            <thead style="border-bottom: 1px solid var(--color-border);">
                                <tr>
                                    <th scope="col" class="text-center text-sm fw-semibold">Tarefa</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Descrição</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Data de conclusão</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Status</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($tarefas)): ?>
                                    <?php foreach ($tarefas as $t): ?>
                                        <tr data-id="<?= $escape($t['id_tarefa'] ?? '') ?>">
                                            <td class="text-center text-sm align-middle" data-field="tarefa"><?= $escape($t['titulo'] ?? '') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="descricao">
                                                <span class="d-inline-block text-truncate align-middle" style="max-width: 280px;" title="<?= $escape($t['conteudo'] ?? '') ?>">
                                                    <?= $escape($t['conteudo'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td class="text-center text-sm align-middle" data-field="data_conclusao"><?= $escape($formatarData($t['data_conclusao'] ?? '') ?: '-') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="status"><?= $escape(!empty($t['status']) ? $t['status'] : 'Pendente') ?></td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-sm btn-table-action btn-table-edit" href="/tarefas/<?= $escape($t['id_tarefa'] ?? '') ?>/editar" aria-label="Editar tarefa" title="Editar">
                                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                                    </a>
                                                    <a class="btn btn-sm btn-table-action btn-table-view" href="/tarefas/<?= $escape($t['id_tarefa'] ?? '') ?>" aria-label="Visualizar tarefa" title="Visualizar">
                                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
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
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
