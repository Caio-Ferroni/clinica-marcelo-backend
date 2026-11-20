<?php
$dadosPerfil = $perfil[0] ?? [];
$escaparPerfil = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meu perfil | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body>
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="profile"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 800px;">
                    <h1 class="text-2xl fw-semibold mb-7">Meu perfil</h1>
                    <?php if ($dadosPerfil === []): ?>
                        <p role="alert" data-toast-type="error">Perfil não encontrado.</p>
                    <?php else: ?>
                        <dl class="border rounded-4 p-7" style="background: var(--color-surface);">
                            <dt class="text-sm mb-3">E-mail</dt>
                            <dd class="text-sm mb-5"><?= $escaparPerfil($dadosPerfil['email']) ?></dd>
                            <dt class="text-sm mb-3">Telefone</dt>
                            <dd class="text-sm mb-0"><?= $escaparPerfil(($dadosPerfil['telefone'] ?? '') ?: 'Não informado') ?></dd>
                        </dl>
                    <?php endif; ?>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
