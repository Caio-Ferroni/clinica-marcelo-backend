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
$relatorio = $consulta['relatorio'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar Consulta | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
</head>
<body class="appointments-edit">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="appointments"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 900px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-3">Atualizar consulta</h1>
                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">
                            Os dados gerais da consulta são gerados automaticamente a partir do agendamento. Apenas o <strong>relatório clínico</strong> pode ser alterado nesta tela.
                        </p>
                    </header>

                    <?php if (!empty($erro)): ?>
                        <p class="border rounded-3 p-5 mb-5" role="alert" style="background: var(--color-light-blue);"><?= $escape($erro) ?></p>
                    <?php endif; ?>

                    <form method="post" action="/consultas/<?= $escape($idConsulta) ?>/editar" class="border rounded-4 shadow-sm p-7 md:p-9" style="background: var(--color-surface);">
                        <input type="hidden" name="id_consulta" value="<?= $escape($idConsulta) ?>">

                        <!-- Informações da consulta (somente leitura) -->
                        <section aria-labelledby="heading-dados-consulta" class="mb-7">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-5">
                                <h2 id="heading-dados-consulta" class="text-md fw-semibold d-flex align-items-center gap-3 m-0">
                                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                                    Dados da consulta <span class="badge text-xs fw-normal bg-secondary-subtle text-secondary-emphasis">Somente leitura</span>
                                </h2>
                                <span class="badge bg-primary-subtle text-primary-emphasis px-3 py-2 text-xs">
                                    Consulta #<?= $escape($idConsulta) ?>
                                </span>
                            </div>

                            <div class="row g-5">
                                <div class="col-12 md:col-6">
                                    <label class="form-label text-sm" for="info-paciente">Paciente</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person" aria-hidden="true"></i></span>
                                        <input class="form-control" type="text" id="info-paciente" value="<?= $escape($nomePaciente) ?>" readonly disabled>
                                    </div>
                                    <?php if (!empty($idPaciente)): ?>
                                        <small class="d-block mt-1">
                                            <a href="/pacientes/<?= $escape($idPaciente) ?>" class="text-decoration-none text-xs">
                                                <i class="bi bi-box-arrow-up-right me-1" aria-hidden="true"></i>Ver perfil do paciente
                                            </a>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 md:col-6">
                                    <label class="form-label text-sm" for="info-telefone">Telefone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-telephone" aria-hidden="true"></i></span>
                                        <input class="form-control" type="text" id="info-telefone" value="<?= $escape($telefone) ?>" readonly disabled>
                                    </div>
                                </div>
                                <div class="col-12 md:col-4">
                                    <label class="form-label text-sm" for="info-data">Data</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-calendar" aria-hidden="true"></i></span>
                                        <input class="form-control" type="text" id="info-data" value="<?= $escape($dataConsulta) ?>" readonly disabled>
                                    </div>
                                </div>
                                <div class="col-12 md:col-4">
                                    <label class="form-label text-sm" for="info-horario">Horário</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-clock" aria-hidden="true"></i></span>
                                        <input class="form-control" type="text" id="info-horario" value="<?= $escape($horarioCompleto) ?>" readonly disabled>
                                    </div>
                                </div>
                                <div class="col-12 md:col-4">
                                    <label class="form-label text-sm" for="info-status">Status</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-check-circle" aria-hidden="true"></i></span>
                                        <input class="form-control" type="text" id="info-status" value="<?= $escape($status) ?>" readonly disabled>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <hr class="my-7" style="border-top: 1px dashed var(--color-border); opacity: 1;">

                        <!-- Relatório da consulta (campo único editável) -->
                        <section aria-labelledby="heading-relatorio">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                                <h2 id="heading-relatorio" class="text-md fw-semibold d-flex align-items-center gap-3 m-0">
                                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                                    Relatório da consulta <span class="badge text-xs fw-normal bg-success-subtle text-success-emphasis">Editável</span>
                                </h2>
                            </div>
                            <p class="text-sm mb-4" style="color: var(--color-text-muted);">
                                Registre a evolução da sessão, anotações de acompanhamento, técnicas aplicadas e observações clínicas relevantes.
                            </p>

                            <div class="mb-5">
                                <label class="form-label text-sm fw-semibold" for="relatorio">
                                    Conteúdo do relatório
                                </label>
                                <textarea class="form-control" id="relatorio" name="relatorio" rows="9" placeholder="Digite aqui o relatório completo do atendimento..."><?= $escape($relatorio) ?></textarea>
                                <small class="d-block mt-2 text-xs" style="color: var(--color-text-muted);">
                                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Apenas este campo será atualizado no banco de dados.
                                </small>
                            </div>
                        </section>

                        <!-- Botões de ação -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-4 mt-7 pt-5 border-top">
                            <a class="btn-outline px-6 py-3 text-decoration-none" href="/consultas">
                                <i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Voltar para consultas
                            </a>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <?php if (!empty($idConsulta)): ?>
                                    <a class="btn-outline px-6 py-3 text-decoration-none" href="/consultas/<?= $escape($idConsulta) ?>">
                                        <i class="bi bi-eye me-2" aria-hidden="true"></i>Visualizar consulta
                                    </a>
                                <?php endif; ?>
                                <button class="btn-solid px-7 py-3" type="submit">
                                    <i class="bi bi-check-lg me-2" aria-hidden="true"></i>Salvar relatório
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>
</body>
</html>