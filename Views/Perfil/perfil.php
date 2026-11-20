<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
</head>
<body class="profiles-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Perfil</h1>
                    </header>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <div class="mb-5">
                        <a class="btn-outline px-7 py-4 text-decoration-none d-inline-flex align-items-center gap-3" href="perfil-create.html">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            Adicionar perfil
                        </a>
                    </div>
                    <div class="border rounded-4 shadow-sm table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-perfis">
                            <caption class="visually-hidden">Lista de perfis de pacientes</caption>
                            <thead style="border-bottom: 1px solid var(--color-border);"><tr><th scope="col" class="text-center text-sm fw-semibold">Nome</th><th scope="col" class="text-center text-sm fw-semibold">Quadro</th><th scope="col" class="text-center text-sm fw-semibold">Descrição</th><th scope="col" class="text-center text-sm fw-semibold">Ações</th></tr></thead>
                            <tbody>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">João</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Joana</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Jonas</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Aline</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Madalena</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Renato</td><td class="text-center text-sm align-middle" data-field="quadro">Lorem ipsum</td><td class="text-center text-sm align-middle" data-field="descricao">Lorem ipsum dolor sit amet consectetur adipiscing elit...</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="perfil-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="perfil-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
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