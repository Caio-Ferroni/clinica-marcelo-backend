<?php
$tarefa = is_array($tarefa ?? null) ? $tarefa : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};
$formatarDataHora = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d H:i:s', substr((string) $valor, 0, 19));
    return $data ? $data->format('d/m/Y \à\s H:i') : (string) $valor;
};

$idTarefa = $tarefa['id_tarefa'] ?? '';
$titulo = $tarefa['titulo'] ?? 'Tarefa sem título';
$conteudo = trim((string) ($tarefa['conteudo'] ?? ''));
$dataConclusao = !empty($tarefa['data_conclusao']) ? $formatarData($tarefa['data_conclusao']) : '';
$status = !empty($tarefa['status']) ? $tarefa['status'] : 'Pendente';
$criadoEm = !empty($tarefa['criado_em']) ? $formatarDataHora($tarefa['criado_em']) : '';
$atualizadoEm = !empty($tarefa['atualizado_em']) ? $formatarDataHora($tarefa['atualizado_em']) : '';

$statusLower = mb_strtolower(trim((string) $status));
$badgeClass = match (true) {
    in_array($statusLower, ['concluída', 'concluida', 'concluído', 'concluido']) => 'bg-success-subtle text-success-emphasis border border-success-subtle',
    in_array($statusLower, ['cancelada', 'cancelado']) => 'bg-danger-subtle text-danger-emphasis border border-danger-subtle',
    default => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
};
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Tarefa | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body class="task-show">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="tasks"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 900px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-3">Visualizar tarefa</h1>
                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">
                            Detalhes da tarefa, anotações e prazo de conclusão.
                        </p>
                    </header>

                    <?php if (empty($tarefa)): ?>
                        <div class="border rounded-4 shadow-sm p-7 text-center" style="background: var(--color-surface);">
                            <p class="mb-5">Nenhuma informação de tarefa disponível.</p>
                            <a class="btn-outline px-6 py-3 text-decoration-none" href="/tarefas">Voltar para tarefas</a>
                        </div>
                    <?php else: ?>
                        <div class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">
                            <!-- Cabeçalho do Card -->
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-5 border-bottom mb-6">
                                <div>
                                    <span class="text-xs text-uppercase fw-semibold" style="color: var(--color-text-muted);">Tarefa #<?= $escape($idTarefa) ?></span>
                                    <h2 class="text-lg fw-semibold mb-0"><?= $escape($titulo) ?></h2>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge <?= $badgeClass ?> px-3 py-2 text-xs">
                                        <?= $escape($status) ?>
                                    </span>
                                    <a class="btn-solid btn-sm px-4 py-2 text-decoration-none d-inline-flex align-items-center gap-2" href="/tarefas/<?= $escape($idTarefa) ?>/editar">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span>Editar tarefa</span>
                                    </a>
                                </div>
                            </div>

                            <!-- Informações Gerais da Tarefa -->
                            <section aria-labelledby="heading-tarefa-info" class="mb-7">
                                <h3 id="heading-tarefa-info" class="text-md fw-semibold d-flex align-items-center gap-3 mb-5">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    Informações da tarefa
                                </h3>
                                <dl class="row g-5 mb-0">
                                    <div class="col-12 md:col-6">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Título</dt>
                                        <dd class="border rounded-3 p-4 mb-0 fw-medium"><?= $escape($titulo) ?></dd>
                                    </div>
                                    <div class="col-12 md:col-3">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Data de conclusão</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($dataConclusao !== '' ? $dataConclusao : 'Não informada') ?></dd>
                                    </div>
                                    <div class="col-12 md:col-3">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Status</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($status) ?></dd>
                                    </div>
                                    <?php if (!empty($criadoEm)): ?>
                                        <div class="col-12 md:col-6">
                                            <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Criada em</dt>
                                            <dd class="border rounded-3 p-4 mb-0"><?= $escape($criadoEm) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($atualizadoEm)): ?>
                                        <div class="col-12 md:col-6">
                                            <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Última atualização</dt>
                                            <dd class="border rounded-3 p-4 mb-0"><?= $escape($atualizadoEm) ?></dd>
                                        </div>
                                    <?php endif; ?>
                                </dl>
                            </section>

                            <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">

                            <!-- Conteúdo / Descrição -->
                            <section aria-labelledby="heading-tarefa-conteudo">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                    <h3 id="heading-tarefa-conteudo" class="text-md fw-semibold d-flex align-items-center gap-3 m-0">
                                        <i class="bi bi-card-text" aria-hidden="true"></i>
                                        Descrição / Conteúdo
                                    </h3>
                                    <a href="/tarefas/<?= $escape($idTarefa) ?>/editar" class="text-decoration-none text-sm fw-medium">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Editar descrição
                                    </a>
                                </div>

                                <?php if ($conteudo !== ''): ?>
                                    <div class="border rounded-3 p-5" style="background: var(--color-field-background); white-space: pre-wrap; line-height: 1.6;"><?= $escape($conteudo) ?></div>
                                <?php else: ?>
                                    <div class="border rounded-3 p-6 text-center text-muted" style="background: var(--color-field-background);">
                                        <i class="bi bi-journal-text text-2xl d-block mb-3" aria-hidden="true"></i>
                                        <p class="mb-3 text-sm">Esta tarefa ainda não possui descrição detalhada.</p>
                                        <a class="btn-solid btn-sm px-4 py-2 text-decoration-none" href="/tarefas/<?= $escape($idTarefa) ?>/editar">
                                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Adicionar descrição
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </section>

                            <!-- Rodapé de Ações -->
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mt-7 pt-5 border-top">
                                <a class="btn-outline px-6 py-3 text-decoration-none" href="/tarefas">
                                    <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Voltar para tarefas
                                </a>
                                <a class="btn-solid px-6 py-3 text-decoration-none" href="/tarefas/<?= $escape($idTarefa) ?>/editar">
                                    <i class="bi bi-pencil-fill me-2" aria-hidden="true"></i>Editar tarefa
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
