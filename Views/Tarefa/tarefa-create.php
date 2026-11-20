<?php
$valores = $valores ?? [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar tarefa | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body class="patient-create">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="tasks"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 800px;">
                    <h1 class="text-2xl fw-semibold text-center mb-7">Adicionar tarefa</h1>
                    <?php if (!empty($erro)): ?>
                        <p class="border rounded-3 p-5 mb-5" role="alert" data-toast-type="<?= $toastType ?? 'error' ?>" style="background: var(--color-light-blue);"><?= $escape($erro) ?></p>
                    <?php endif; ?>
                    <form method="post" action="/tarefas/criar" data-toast-validate class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">
                        <div class="mb-5">
                            <label class="form-label" for="task-title">Título</label>
                            <input class="form-control" type="text" id="task-title" name="titulo" value="<?= $escape($valores['titulo'] ?? '') ?>" required>
                        </div>
                        <div class="mb-5">
                            <label class="form-label" for="task-content">Conteúdo</label>
                            <textarea class="form-control" id="task-content" name="conteudo" rows="5" required><?= $escape($valores['conteudo'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-7">
                            <label class="form-label" for="task-date">Data de conclusão</label>
                            <input class="form-control" type="date" id="task-date" name="data_conclusao" value="<?= $escape($valores['data_conclusao'] ?? '') ?>" required>
                        </div>
                        <div class="d-flex flex-wrap justify-content-end gap-4">
                            <a class="btn-outline" href="/tarefas">Cancelar</a>
                            <button class="btn-solid" type="submit">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                Adicionar tarefa
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
