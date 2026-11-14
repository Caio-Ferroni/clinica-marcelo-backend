<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
</head>
<body class="patient-create auth">
    <div class="d-flex flex-column min-vh-100">
        <main class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-5 md:p-9">
            
            <div class="w-100" style="max-width: 560px;">
                <header class="text-center mb-7">
                    <h1 class="text-2xl fw-semibold mb-0">Cadastrar</h1>
                </header>
                <div class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">
                    <h2 class="text-md fw-semibold mb-5">Cadastrar usuário</h2>

                    
                    <form id="auth-form" action="/login" method="post" novalidate>
                        <div class="mb-5">
                            <label class="form-label text-sm" for="email">E-mail</label>
                            <input class="form-control" type="email" id="email" name="email" placeholder="voce@exemplo.com.br" autocomplete="email" required>
                        </div>
                        <div class="row g-5 mb-7">
                            <div class="col-12 md:col-6">
                                <div class="mb-5">
                                    <label class="form-label text-sm" for="senha">Senha</label>
                                    <input class="form-control" type="password" id="senha" name="senha" placeholder="Mínimo de 8 caracteres" autocomplete="new-password" minlength="8" required>
                                </div>
                            </div>
                            <div class="col-12 md:col-6">
                                <div class="mb-5">
                                    <label class="form-label text-sm" for="confirmar-senha">Confirmar senha</label>
                                    <input class="form-control" type="password" id="confirmar-senha" name="confirmar_senha" placeholder="Repita a senha" autocomplete="new-password" minlength="8" required>
                                </div>
                            </div>
                        </div>
                        <div class="text-center"><button class="btn-solid px-7 py-4" type="submit">Cadastrar</button></div>
                    </form>
                    <p id="form-message" class="border rounded-3 p-5 mt-5 mb-0 text-sm" role="alert" hidden></p>
                    <!-- <p class="text-sm text-center mt-5 mb-0">Já tem conta? <a href="login.html">Entrar</a></p> -->
                </div>
            </div>
        </main>
        <clinic-footer></clinic-footer>
    </div>
</body>
</html>