<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autenticação em duas etapas | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
</head>
<body class="patient-create auth">
    <div class="d-flex flex-column min-vh-100">
        <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-5 md:p-9">
            
                <header class="text-center mb-7">
                    <h1 class="text-2xl fw-semibold mb-0">Autenticação em duas etapas</h1>
                </header>
                <div class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">
                    <h2 class="text-md fw-semibold mb-5">Verifique seu e-mail</h2>
                    <p class="text-sm text-center mb-7" style="color: var(--color-text-muted);">Um código de verificação de 6 dígitos foi enviado ao seu e-mail.<br>Digite o código na caixa abaixo para acessar sua conta.</p>
                    
                    <form id="auth-form" action="/2fa" method="post" data-toast-validate>
                        <div class="mb-7 mx-auto" style="max-width: 240px;">
                            <label class="form-label text-sm" for="codigo">Código de verificação</label>
                            <input class="form-control text-center" type="text" id="codigo" name="codigo" placeholder="000000" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required>
                        </div>
                        <div class="text-center"><button class="btn-solid px-7 py-4" type="submit">Avançar</button></div>
                    </form>
                    <?php if (!empty($erro)): ?>
                        <p id="form-message" class="border rounded-3 p-5 mt-5 mb-0 text-sm" role="alert" data-toast-type="<?= $toastType ?? 'error' ?>"><?= htmlspecialchars($erro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php endif; ?>

                </div>
        </main>
        <clinic-footer></clinic-footer>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
