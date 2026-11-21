<?php
$pacientes = is_array($pacientes ?? null) ? $pacientes : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$totalPacientes = count($pacientes);
$gruposPacientes = [
    'Ativo' => ['rotulo' => 'Ativos', 'titulo' => 'Pacientes ativos', 'total' => count(array_filter($pacientes, static fn($p) => ($p['status'] ?? '') === 'Ativo')), 'vazio' => 'Nenhum paciente ativo.'],
    'Inativo' => ['rotulo' => 'Inativos', 'titulo' => 'Pacientes inativos', 'total' => count(array_filter($pacientes, static fn($p) => ($p['status'] ?? '') === 'Inativo')), 'vazio' => 'Nenhum paciente inativo.'],
    'Todos' => ['rotulo' => 'Todos', 'titulo' => 'Todos os pacientes', 'total' => $totalPacientes, 'vazio' => 'Nenhum paciente cadastrado.']
];
$statusSelecionado = is_string($_GET['status'] ?? null) && isset($gruposPacientes[$_GET['status']]) ? $_GET['status'] : 'Ativo';
$timezone = new DateTimeZone('America/Sao_Paulo');
$hoje = (new DateTime('now', $timezone))->format('Y-m-d');
$limite30Dias = (new DateTime('-30 days', $timezone))->format('Y-m-d H:i:s');

$pacientesHoje = 0;
$pacientesNovosCount = 0;

foreach ($pacientes as $p) {
    $criadoEm = (string) ($p['criado_em'] ?? '');
    if (!empty($criadoEm)) {
        if (str_starts_with($criadoEm, $hoje)) {
            $pacientesHoje++;
        }
        if ($criadoEm >= $limite30Dias) {
            $pacientesNovosCount++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/patient-tabs.css?v=<?= filemtime(__DIR__ . '/../../css/patient-tabs.css') ?>">
    <script src="/js/layout.js?v=<?= filemtime(__DIR__ . '/../../js/layout.js') ?>" defer></script>
</head>
<body class="patients-list">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-0">Pacientes</h1>
                    </header>
                    <div class="row g-5 mb-7">
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Pacientes totais</p>
                                <p class="text-lg fw-semibold mb-0" data-field="pacientes_totais"><?= $totalPacientes ?> <?= $totalPacientes === 1 ? 'Paciente' : 'Pacientes' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Pacientes no ultimo mês</p>
                                <p class="text-lg fw-semibold mb-0" data-field="pacientes_novos"><?= $pacientesNovosCount > 0 ? "+{$pacientesNovosCount}" : '0' ?> <?= $pacientesNovosCount === 1 ? 'Paciente' : 'Pacientes' ?></p>
                            </div>
                        </div>
                        <div class="col-12 md:col-4">
                            <div class="border rounded-4 shadow-sm p-6 text-center h-100" style="background: var(--color-surface);">
                                <p class="text-sm mb-2" style="color: var(--color-text-muted);">Pacientes hoje</p>
                                <p class="text-lg fw-semibold mb-0" data-field="pacientes_hoje"><?= $pacientesHoje ?> <?= $pacientesHoje === 1 ? 'Paciente' : 'Pacientes' ?></p>
                            </div>
                        </div>
                    </div>
                    <hr class="mb-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <div class="mb-5">
                        <a class="btn-outline px-7 py-4 text-decoration-none d-inline-flex align-items-center gap-3" href="/pacientes/criar">
                            <i class="bi bi-person-plus" aria-hidden="true"></i>
                            Adicionar paciente
                        </a>
                    </div>
                    <div class="patient-tabs" role="tablist" aria-label="Filtrar pacientes por status">
                        <?php foreach ($gruposPacientes as $status => $grupo): ?>
                            <a id="patient-tab-<?= $escape($status) ?>" class="patient-tab" role="tab" href="/pacientes?status=<?= $escape($status) ?>" aria-controls="patient-list-panel" aria-selected="<?= $statusSelecionado === $status ? 'true' : 'false' ?>" tabindex="<?= $statusSelecionado === $status ? '0' : '-1' ?>" data-patient-status="<?= $escape($status) ?>" data-patient-title="<?= $escape($grupo['titulo']) ?>" data-patient-empty="<?= $escape($grupo['vazio']) ?>">
                                <?= $escape($grupo['rotulo']) ?> <span class="patient-tab-count">(<?= $grupo['total'] ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <section class="mb-7" id="patient-list-panel" role="tabpanel" aria-labelledby="patient-tab-<?= $escape($statusSelecionado) ?>">
                        <div class="border rounded-4 shadow-sm table-responsive" style="background: var(--color-surface);">
                        <table class="table table-striped table-hover table-borderless align-middle mb-0" id="tabela-pacientes" data-status-filter="<?= $escape($statusSelecionado) ?>">
                            <caption class="visually-hidden"><?= $escape($gruposPacientes[$statusSelecionado]['titulo']) ?></caption>
                            <thead style="border-bottom: 1px solid var(--color-border);">
                                <tr>
                                    <th scope="col" class="text-center text-sm fw-semibold">Nome</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Telefone</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">CPF</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Status</th>
                                    <th scope="col" class="text-center text-sm fw-semibold">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pacientes)): ?>
                                    <?php foreach ($pacientes as $p): ?>
                                        <tr data-id="<?= $escape($p['id_paciente'] ?? '') ?>" data-status="<?= $escape($p['status'] ?? '') ?>" <?= $statusSelecionado === 'Todos' || ($p['status'] ?? '') === $statusSelecionado ? '' : 'hidden' ?>>
                                            <td class="text-center text-sm align-middle" data-field="nome"><?= $escape($p['nome'] ?? '') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="telefone"><?= $escape($p['telefone'] ?? '') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="cpf"><?= $escape($p['cpf'] ?? '') ?></td>
                                            <td class="text-center text-sm align-middle" data-field="status"><?= $escape($p['status'] ?? 'Ativo') ?></td>
                                            <td class="text-center align-middle">
                                                <div class="d-flex justify-content-center gap-3">
                                                    <a class="btn btn-sm p-2" href="/pacientes/<?= $escape($p['id_paciente'] ?? '') ?>/editar" aria-label="Editar">
                                                        <i class="bi bi-pencil-fill" aria-hidden="true"></i>
                                                    </a>
                                                    <a class="btn btn-sm p-2" href="/pacientes/<?= $escape($p['id_paciente'] ?? '') ?>" aria-label="Visualizar">
                                                        <i class="bi bi-eye-fill" aria-hidden="true"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <tr data-empty-row <?= $gruposPacientes[$statusSelecionado]['total'] === 0 ? '' : 'hidden' ?>><td colspan="5" class="text-center text-sm p-7"><?= $escape($gruposPacientes[$statusSelecionado]['vazio']) ?></td></tr>
                            </tbody>
                        </table>
                        </div>
                    </section>
                    <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">
                    <section aria-labelledby="novos-heading">
                        <h2 id="novos-heading" class="text-md fw-semibold mb-4">Pacientes novos</h2>
                        <div class="border rounded-4 shadow-sm p-6" style="background: var(--color-surface);">
                            <?php
                            $listaNovos = array_filter($pacientes, static fn($p) => !empty($p['criado_em']) && (string) $p['criado_em'] >= $limite30Dias);
                            ?>
                            <?php if (empty($listaNovos)): ?>
                                <p class="text-sm text-center mb-0" style="color: var(--color-text-muted);">Nenhum paciente cadastrado recentemente.</p>
                            <?php else: ?>
                                <?php
                                $pacientesNovos = array_slice(array_reverse(array_values($listaNovos)), 0, 8);
                                $metade = (int) ceil(count($pacientesNovos) / 2);
                                $coluna1 = array_slice($pacientesNovos, 0, $metade);
                                $coluna2 = array_slice($pacientesNovos, $metade);
                                ?>
                                <div class="row g-6">
                                    <div class="col-12 md:col-6">
                                        <ul class="list-unstyled d-flex flex-column gap-4 mb-0">
                                            <?php foreach ($coluna1 as $pn): ?>
                                                <li class="d-flex align-items-center justify-content-between gap-4">
                                                    <a href="/pacientes/<?= $escape($pn['id_paciente'] ?? '') ?>" class="border rounded-3 px-5 py-3 flex-grow-1 text-sm text-decoration-none" style="color: inherit;">
                                                        <?= $escape($pn['nome'] ?? '') ?>
                                                    </a>
                                                    <small class="text-xs text-end" style="color: var(--color-text-muted);"><?= $escape($pn['status'] ?? 'Ativo') ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <ul class="list-unstyled d-flex flex-column gap-4 mb-0">
                                            <?php foreach ($coluna2 as $pn): ?>
                                                <li class="d-flex align-items-center justify-content-between gap-4">
                                                    <a href="/pacientes/<?= $escape($pn['id_paciente'] ?? '') ?>" class="border rounded-3 px-5 py-3 flex-grow-1 text-sm text-decoration-none" style="color: inherit;">
                                                        <?= $escape($pn['nome'] ?? '') ?>
                                                    </a>
                                                    <small class="text-xs text-end" style="color: var(--color-text-muted);"><?= $escape($pn['status'] ?? 'Ativo') ?></small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
