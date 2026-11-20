<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página indisponível | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body>
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="<?= htmlspecialchars($secao, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></clinic-sidebar>
        <main class="flex-grow-1 p-7 md:p-9">
            <h1 class="text-2xl fw-semibold mb-7">Não foi possível abrir esta página</h1>
            <p role="alert" data-toast-type="error"><?= htmlspecialchars($erro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
            <a class="btn-outline text-decoration-none" href="<?= htmlspecialchars($voltar, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">Voltar</a>
        </main>
    </div>
    <?php require __DIR__ . '/toasts.php'; ?>
</body>
</html>
