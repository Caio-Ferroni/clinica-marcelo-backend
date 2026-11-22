<?php
require_once __DIR__ . '/../../Backend/Auth/Auth.php';
require_once __DIR__ . '/../../Backend/Support/EnvioPaciente.php';
$csrfFormulario = Auth::csrfToken();
$envioFormulario = EnvioPaciente::token('criar', $_POST['envio_token'] ?? null);
$valores = $valores ?? [];
$abrirAgendamento = ($valores['dia_semana'] ?? '') !== ''
    || ($valores['repeticao'] ?? '') !== ''
    || ($valores['horario_inicio'] ?? '') !== ''
    || ($valores['horario_termino'] ?? '') !== ''
    || ($valores['data_inicio'] ?? '') !== '';
$abrirPerfil = ($valores['quadro'] ?? '') !== '' || ($valores['descricao'] ?? '') !== '';
$abrirEncaminhamento = ($valores['tipo_encaminhamento'] ?? '') !== '' || ($valores['historico_paciente'] ?? '') !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar paciente | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css?v=<?= filemtime(__DIR__ . '/../../css/style.css') ?>">
    <script src="/js/layout.js?v=<?= filemtime(__DIR__ . '/../../js/layout.js') ?>" defer></script>
    <script src="/js/patient-masks.js" defer></script>
    <script src="/js/patient-schedule.js?v=<?= filemtime(__DIR__ . '/../../js/patient-schedule.js') ?>" defer></script>
</head>
<body class="patient-create">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-3">Adicionar paciente</h1>
                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">Preencha os dados pessoais e adicione as informações do atendimento.</p>
                    </header>

                    <?php if (!empty($erro)): ?>
                        <p class="border rounded-3 p-5 mb-5" role="alert" data-toast-type="<?= $toastType ?? 'warning' ?>" style="background: var(--color-light-blue);"><?= htmlspecialchars($erro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                    <?php endif; ?>

                    <form id="patient-form" method="post" action="/pacientes/criar" data-toast-validate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfFormulario, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <input type="hidden" name="envio_token" value="<?= htmlspecialchars($envioFormulario, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="border rounded-4 shadow-sm" style="background: var(--color-surface);">
                            <section class="p-7 md:p-9" aria-labelledby="personal-heading">
                                <h2 id="personal-heading" class="text-md fw-semibold d-flex align-items-center gap-3 mb-7">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    Dados pessoais
                                </h2>
                                <div class="row g-5">
                                    <div class="col-12 md:col-8">
                                        <label class="form-label text-sm" for="patient-name">Nome completo <span aria-hidden="true">*</span></label>
                                        <input class="form-control" type="text" id="patient-name" name="nome" value="<?= htmlspecialchars(($valores['nome'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Digite o nome do paciente" autocomplete="name" required>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <label class="form-label text-sm" for="birth-date">Data de nascimento</label>
                                        <input class="form-control" type="date" id="birth-date" name="data_nascimento" required value="<?= htmlspecialchars(($valores['data_nascimento'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" autocomplete="bday">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-cpf">CPF</label>
                                        <input class="form-control" type="text" id="patient-cpf" name="cpf" required value="<?= htmlspecialchars(($valores['cpf'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" data-mask="cpf">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-phone">Telefone</label>
                                        <input class="form-control" type="tel" id="patient-phone" name="telefone" required value="<?= htmlspecialchars(($valores['telefone'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="(00) 00000-0000" autocomplete="tel" inputmode="numeric" maxlength="15" data-mask="telefone">
                                    </div>
                                </div>
                            </section>

                            <details class="optional-section p-7 md:p-9" id="schedule-section" <?= $abrirAgendamento ? 'open' : '' ?>>
                                <summary>
                                    <span class="section-prompt btn-outline px-7 py-4">
                                        <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                        Criar agendamento
                                    </span>
                                    <span class="section-heading align-items-center justify-content-between gap-4 fw-semibold">
                                        <span><i class="bi bi-calendar-event me-3" aria-hidden="true"></i>Dados de agendamento</span>
                                        <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                    </span>
                                </summary>
                                <fieldset class="m-0 p-0 border-0" <?= $abrirAgendamento ? '' : 'disabled' ?>>
                                    <legend class="visually-hidden">Dados de agendamento</legend>
                                    <p class="text-sm mb-5" style="color: var(--color-text-muted);">As consultas futuras deste mês serão geradas ao salvar. A repetição começa no primeiro dia da semana selecionado que ocorrer na data inicial ou depois dela. Horários ocupados impedem o salvamento.</p>
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="week-day">Dia da semana</label>
                                            <select class="form-control" id="week-day" name="dia_semana">
                                                <option value="">Selecione o dia</option>
                                                <option value="1" <?= (string) ($valores['dia_semana'] ?? '') === '1' ? 'selected' : '' ?>>Segunda-feira</option>
                                                <option value="2" <?= (string) ($valores['dia_semana'] ?? '') === '2' ? 'selected' : '' ?>>Terça-feira</option>
                                                <option value="3" <?= (string) ($valores['dia_semana'] ?? '') === '3' ? 'selected' : '' ?>>Quarta-feira</option>
                                                <option value="4" <?= (string) ($valores['dia_semana'] ?? '') === '4' ? 'selected' : '' ?>>Quinta-feira</option>
                                                <option value="5" <?= (string) ($valores['dia_semana'] ?? '') === '5' ? 'selected' : '' ?>>Sexta-feira</option>
                                                <option value="6" <?= (string) ($valores['dia_semana'] ?? '') === '6' ? 'selected' : '' ?>>Sábado</option>
                                                <option value="0" <?= (string) ($valores['dia_semana'] ?? '') === '0' ? 'selected' : '' ?>>Domingo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="repeat-interval">Repetição</label>
                                            <select class="form-control" id="repeat-interval" name="repeticao">
                                                <option value="">Selecione a frequência</option>
                                                <option value="Semanal" <?= ($valores['repeticao'] ?? '') === 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                                                <option value="Quinzenal" <?= ($valores['repeticao'] ?? '') === 'Quinzenal' ? 'selected' : '' ?>>Quinzenal</option>
                                                <option value="Mensal" <?= ($valores['repeticao'] ?? '') === 'Mensal' ? 'selected' : '' ?>>Mensal</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-time">Horário de início</label>
                                            <input class="form-control" type="time" id="start-time" name="horario_inicio" value="<?= htmlspecialchars(($valores['horario_inicio'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="end-time">Horário de término</label>
                                            <input class="form-control" type="time" id="end-time" name="horario_termino" value="<?= htmlspecialchars(($valores['horario_termino'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-date">Data de início</label>
                                            <input class="form-control" type="date" id="start-date" name="data_inicio" value="<?= htmlspecialchars(($valores['data_inicio'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <?php require __DIR__ . '/horarios-livres.php'; ?>
                                </fieldset>
                            </details>

                            <details class="optional-section p-7 md:p-9" id="profile-section" <?= $abrirPerfil ? 'open' : '' ?>>
                                <summary>
                                    <span class="section-prompt btn-outline px-7 py-4">
                                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                                        Criar perfil
                                    </span>
                                    <span class="section-heading align-items-center justify-content-between gap-4 fw-semibold">
                                        <span><i class="bi bi-person-vcard me-3" aria-hidden="true"></i>Perfil do paciente</span>
                                        <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                    </span>
                                </summary>
                                <fieldset class="m-0 p-0 border-0" <?= $abrirPerfil ? '' : 'disabled' ?>>
                                    <legend class="visually-hidden">Perfil do paciente</legend>
                                    <div class="mb-5">
                                        <label class="form-label text-sm" for="patient-condition">Quadro</label>
                                        <input class="form-control" type="text" id="patient-condition" name="quadro" value="<?= htmlspecialchars(($valores['quadro'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Informe o quadro do paciente">
                                    </div>
                                    <div>
                                        <label class="form-label text-sm" for="patient-description">Descrição</label>
                                        <textarea class="form-control" id="patient-description" name="descricao" rows="5" placeholder="Descreva as informações relevantes para o acompanhamento."><?= htmlspecialchars(($valores['descricao'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                                    </div>
                                </fieldset>
                            </details>

                            <details class="optional-section p-7 md:p-9" id="referral-section" <?= $abrirEncaminhamento ? 'open' : '' ?>>
                                <summary>
                                    <span class="section-prompt btn-outline px-7 py-4">
                                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                        Criar encaminhamento
                                    </span>
                                    <span class="section-heading align-items-center justify-content-between gap-4 fw-semibold">
                                        <span><i class="bi bi-box-arrow-up-right me-3" aria-hidden="true"></i>Encaminhamento</span>
                                        <i class="bi bi-chevron-up" aria-hidden="true"></i>
                                    </span>
                                </summary>
                                <fieldset class="m-0 p-0 border-0" <?= $abrirEncaminhamento ? '' : 'disabled' ?>>
                                    <legend class="visually-hidden">Encaminhamento</legend>
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="referral-type">Tipo de encaminhamento</label>
                                            <select class="form-control" id="referral-type" name="tipo_encaminhamento">
                                                <option value="">Selecione o tipo</option>
                                                <option value="Recebimento" <?= ($valores['tipo_encaminhamento'] ?? '') === 'Recebimento' ? 'selected' : '' ?>>Recebimento</option>
                                                <option value="Envio" <?= ($valores['tipo_encaminhamento'] ?? '') === 'Envio' ? 'selected' : '' ?>>Envio</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-sm" for="patient-history">Histórico do paciente</label>
                                            <textarea class="form-control" id="patient-history" name="historico_paciente" rows="4" placeholder="Registre as informações do histórico do paciente."><?= htmlspecialchars(($valores['historico_paciente'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                                        </div>
                                    </div>
                                </fieldset>
                            </details>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-5 mt-7">
                            <small style="color: var(--color-text-muted);">* Campo obrigatório</small>
                            <button class="btn-solid px-7 py-4" type="submit">
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                Adicionar paciente
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>

    <script>
        document.querySelectorAll('.optional-section').forEach((section) => {
            section.addEventListener('toggle', () => {
                section.querySelector('fieldset').disabled = !section.open;
            });
        });
    </script>
    <?php require __DIR__ . '/../Shared/toasts.php'; ?>
</body>
</html>
