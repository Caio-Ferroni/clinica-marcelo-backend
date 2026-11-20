<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Encaminhamentos | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
</head>
<body class="referrals-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <!-- PHP (ao migrar para .php): no topo do arquivo, carregar os dados do banco (ex.: $registros = ...) e, se necessário, exigir login. Os caminhos ../../css e ../../js continuam iguais. -->
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Encaminhamentos</h1>
                    </header>
                    <div class="row g-5 mb-7">
                        <div class="col-12 md:col-6">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Pacientes encaminhados</p>
                                <p class="text-lg fw-semibold mb-0" data-field="pacientes_encaminhados">XX</p>
                            </div>
                        </div>
                        <div class="col-12 md:col-6">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Encaminhamentos recebidos</p>
                                <p class="text-lg fw-semibold mb-0" data-field="encaminhamentos_recebidos">XX</p>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <div class="mb-5">
                        <!-- Backend: link para o formulário de criação (será .php) -->
                        <a class="btn-outline px-7 py-4 text-decoration-none d-inline-flex align-items-center gap-3" href="encaminhamento-create.html">
                            <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            Adicionar encaminhamento
                        </a>
                    </div>
                    <div class="border table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-encaminhamentos">
                            <caption class="visually-hidden">Lista de encaminhamentos</caption>
                            <thead style="border-bottom: 1px solid var(--color-border);"><tr><th scope="col" class="text-center text-sm fw-semibold">Nome</th><th scope="col" class="text-center text-sm fw-semibold">Telefone</th><th scope="col" class="text-center text-sm fw-semibold">CPF</th><th scope="col" class="text-center text-sm fw-semibold">Ações</th></tr></thead>
                            <tbody>
                                <!-- PHP (ao migrar para .php): abrir aqui um foreach sobre $registros (tag php de abertura + foreach ($registros as $r): + tag de fechamento).
                                     Em cada [data-field], imprimir o campo com htmlspecialchars($r['campo']); nos links e no data-id, usar (int) $r['id'].
                                     Campos desta tabela: nome, telefone, cpf -->
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">João</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Joana</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Jonas</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Aline</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Madalena</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <tr data-id="1"><td class="text-center text-sm align-middle" data-field="nome">Renato</td><td class="text-center text-sm align-middle" data-field="telefone">(XX) XXXXX-XXXX</td><td class="text-center text-sm align-middle" data-field="cpf">XXX.XXX.XXX-XX</td><td class="text-center align-middle"><div class="d-flex justify-content-center gap-3"><a class="btn btn-sm p-2" href="encaminhamento-edit.html?id=1" aria-label="Editar"><i class="bi bi-pencil-fill" aria-hidden="true"></i></a><a class="btn btn-sm p-2" href="encaminhamento-view.html?id=1" aria-label="Visualizar"><i class="bi bi-eye-fill" aria-hidden="true"></i></a></div></td></tr>
                                <!-- PHP: fechar aqui o loop com endforeach; -->
                                <!-- PHP: se a lista vier vazia, mostrar uma linha com colspan="4" e o texto "Nenhum registro encontrado." -->
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