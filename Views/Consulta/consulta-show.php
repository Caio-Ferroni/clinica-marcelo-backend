<?php
$consulta = is_array($consulta ?? null) ? $consulta : [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$formatarData = static function ($valor) {
    if (empty($valor)) return '';
    $data = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
    return $data ? $data->format('d/m/Y') : (string) $valor;
};
$formatarHorario = static function ($valor) {
    if (empty($valor)) return '';
    return substr((string) $valor, 0, 5);
};

$idConsulta = $consulta['id_consulta'] ?? '';
$nomePaciente = $consulta['paciente_nome'] ?? ($consulta['nome'] ?? 'Paciente não informado');
$idPaciente = $consulta['id_paciente'] ?? null;
$telefone = !empty($consulta['telefone']) ? $consulta['telefone'] : 'Não informado';
$cpf = !empty($consulta['cpf']) ? $consulta['cpf'] : 'Não informado';
$dataNascimento = !empty($consulta['data_nascimento']) ? $formatarData($consulta['data_nascimento']) : 'Não informada';
$dataConsulta = !empty($consulta['data']) ? $formatarData($consulta['data']) : 'Não informada';
$horarioInicio = !empty($consulta['horario_inicio']) ? $formatarHorario($consulta['horario_inicio']) : '-';
$horarioFim = !empty($consulta['horario_fim']) ? $formatarHorario($consulta['horario_fim']) : '-';
$horarioCompleto = ($horarioInicio !== '-' || $horarioFim !== '-') ? "{$horarioInicio} às {$horarioFim}" : '-';
$status = !empty($consulta['status']) ? $consulta['status'] : 'Pendente';
$podeEditar = Consulta::podeEditarRelatorio($consulta);
$atributosEdicao = $podeEditar
    ? 'href="/consultas/' . (int) $idConsulta . '/editar"'
    : 'role="link" aria-disabled="true" tabindex="-1" title="' . $escape(Toast::message('report.not_completed')) . '"';
$relatorio = trim((string) ($consulta['relatorio'] ?? ''));
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Consulta | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>">
    <script src="/js/layout.js?v=<?= filemtime(__DIR__ . '/../../js/layout.js') ?>" defer></script>
</head>
<body class="appointments-show">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="appointments"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 900px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-3">Visualizar consulta</h1>
                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">
                            Detalhes do atendimento e registro do relatório clínico.
                        </p>
                    </header>

                    <?php if (empty($consulta)): ?>
                        <div class="border rounded-4 shadow-sm p-7 text-center" style="background: var(--color-surface);">
                            <p class="mb-5">Nenhuma informação de consulta disponível.</p>
                            <a class="btn-outline px-6 py-3 text-decoration-none" href="/consultas">Voltar para consultas</a>
                        </div>
                    <?php else: ?>
                        <div class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-5 border-bottom mb-6">
                                <div>
                                    <span class="text-xs text-uppercase fw-semibold" style="color: var(--color-text-muted);">Atendimento</span>
                                    <h2 class="text-lg fw-semibold mb-0">Consulta #<?= $escape($idConsulta) ?></h2>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2 text-xs">
                                        <?= $escape($status) ?>
                                    </span>
                                    <a class="btn-solid btn-sm px-4 py-2 text-decoration-none d-inline-flex align-items-center gap-2<?= $podeEditar ? '' : ' disabled opacity-50' ?>" <?= $atributosEdicao ?>>
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                        <span>Editar relatório</span>
                                    </a>
                                </div>
                            </div>


                            <section aria-labelledby="heading-consulta-info" class="mb-7">
                                <h3 id="heading-consulta-info" class="text-md fw-semibold d-flex align-items-center gap-3 mb-5">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    Informações do atendimento
                                </h3>
                                <dl class="row g-5 mb-0">
                                    <div class="col-12 md:col-6">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Paciente</dt>
                                        <dd class="border rounded-3 p-4 mb-0 fw-medium">
                                            <?= $escape($nomePaciente) ?>
                                            <?php if (!empty($idPaciente)): ?>
                                                <a href="/pacientes/<?= $escape($idPaciente) ?>" class="ms-2 text-decoration-none text-xs">
                                                    <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Ver paciente
                                                </a>
                                            <?php endif; ?>
                                        </dd>
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Telefone</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($telefone) ?></dd>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Data da consulta</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($dataConsulta) ?></dd>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Horário</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($horarioCompleto) ?></dd>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <dt class="text-sm fw-semibold mb-2" style="color: var(--color-text-muted);">Status</dt>
                                        <dd class="border rounded-3 p-4 mb-0"><?= $escape($status) ?></dd>
                                    </div>
                                </dl>
                            </section>

                            <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">


                            <section aria-labelledby="heading-relatorio-info">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                    <h3 id="heading-relatorio-info" class="text-md fw-semibold d-flex align-items-center gap-3 m-0">
                                        <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                                        Relatório da consulta
                                    </h3>
                                    <a <?= $atributosEdicao ?> class="text-decoration-none text-sm fw-medium<?= $podeEditar ? '' : ' disabled opacity-50' ?>">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Atualizar relatório
                                    </a>
                                </div>

                                <?php if ($relatorio !== ''): ?>
                                    <div class="border rounded-3 p-5" style="background: var(--color-field-background); white-space: pre-wrap; line-height: 1.6;"><?= $escape($relatorio) ?></div>
                                <?php else: ?>
                                    <div class="border rounded-3 p-6 text-center text-muted" style="background: var(--color-field-background);">
                                        <i class="bi bi-journal-text text-2xl d-block mb-3" aria-hidden="true"></i>
                                        <p class="mb-3 text-sm">Esta consulta ainda não possui relatório.</p>
                                        <a class="btn-solid btn-sm px-4 py-2 text-decoration-none<?= $podeEditar ? '' : ' disabled opacity-50' ?>" <?= $atributosEdicao ?>>
                                            <i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Cadastrar relatório
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!$podeEditar): ?>
                                    <p class="text-sm mt-4 mb-0"><?= $escape(Toast::message('report.not_completed')) ?></p>
                                <?php endif; ?>
                            </section>


                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mt-7 pt-5 border-top">
                                <a class="btn-outline px-6 py-3 text-decoration-none" href="/consultas">
                                    <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Voltar para consultas
                                </a>
                                <a class="btn-solid px-6 py-3 text-decoration-none<?= $podeEditar ? '' : ' disabled opacity-50' ?>" <?= $atributosEdicao ?>>
                                    <i class="bi bi-pencil-fill me-2" aria-hidden="true"></i>Editar relatório
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
