<?php
require_once __DIR__ . '/../../Backend/Auth/Auth.php';
$csrfFormulario = Auth::csrfToken();
$paciente = $paciente ?? [];
$perfil = $perfil ?? [];
$agendamento = $agendamento ?? [];
$encaminhamento = $encaminhamento ?? [];
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar paciente | Clínica</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <script src="/js/layout.js" defer></script>
    <script src="/js/patient-masks.js" defer></script>
</head>
<body class="patient-create patient-edit">
    <div class="d-flex flex-column md:flex-row min-vh-100">
        <clinic-sidebar active="patients"></clinic-sidebar>
        <div class="d-flex flex-column flex-grow-1 min-w-0">
            <main class="flex-grow-1 p-5 md:p-9">
                <div class="mx-auto" style="max-width: 1000px;">
                    <header class="text-center mb-8">
                        <h1 class="text-2xl fw-semibold mb-3">Editar paciente</h1>
                        <p class="text-sm mb-0" style="color: var(--color-text-muted);">Selecione um paciente para consultar e editar suas informações.</p>
                    </header>

                    <?php if (!empty($erro)): ?><p role="alert" data-toast-type="warning"><?= htmlspecialchars($erro, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p><?php endif; ?>
                    <form id="patient-form" data-toast-validate data-patient-edit method="POST" action="/pacientes/<?= (int) ($paciente['id_paciente'] ?? 0)?>/editar">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfFormulario, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <input type="hidden" name="id_paciente" value="<?= htmlspecialchars($paciente['id_paciente'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                        <fieldset class="border-0 p-0 m-0" data-patient-fields <?= empty($paciente) ? 'disabled' : '' ?>>
                            <legend class="visually-hidden">Informações do paciente</legend>
                            <div class="border rounded-4 shadow-sm" style="background: var(--color-surface);">
                                <section class="p-7 md:p-9" aria-labelledby="personal-heading">
                                    <h2 id="personal-heading" class="text-md fw-semibold d-flex align-items-center gap-3 mb-7">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        Dados pessoais
                                    </h2>
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="patient-name">Nome completo <span aria-hidden="true">*</span></label>
                                            <div class="input-group">
                                                <button type="button" class="input-group-text" aria-label="Selecionar outro paciente" style="cursor: pointer;" data-open-patient-search aria-haspopup="dialog" aria-controls="patient-search-modal"><i class="bi bi-search" aria-hidden="true"></i></button>
                                                <input class="form-control" type="text" id="patient-name" name="nome" value="<?= htmlspecialchars($paciente['nome'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Nome completo do paciente" autocomplete="name" required>
                                            </div>
                                        </div>
                                        <div class="col-12 md:col-3">
                                            <label class="form-label text-sm" for="birth-date">Data de nascimento</label>
                                            <input class="form-control" type="date" id="birth-date" name="data_nascimento" required value="<?= htmlspecialchars(substr($paciente['data_nascimento'] ?? '', 0, 10), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" autocomplete="bday">
                                        </div>
                                        <div class="col-12 md:col-3">
                                            <label class="form-label text-sm" for="patient-status">Status</label>
                                            <select class="form-control" id="patient-status" name="status">
                                                <option value="">Selecione</option>
                                                <option value="Ativo" <?= ($paciente['status'] ?? '') === 'Ativo' ? 'selected' : '' ?>>Ativo</option>
                                                <option value="Inativo" <?= ($paciente['status'] ?? '') === 'Inativo' ? 'selected' : '' ?>>Inativo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="patient-cpf">CPF</label>
                                            <input class="form-control" type="text" id="patient-cpf" name="cpf" required value="<?= htmlspecialchars($paciente['cpf'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="000.000.000-00" inputmode="numeric" maxlength="14" data-mask="cpf">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="patient-phone">Telefone</label>
                                            <input class="form-control" type="tel" id="patient-phone" name="telefone" required value="<?= htmlspecialchars($paciente['telefone'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="(00) 00000-0000" autocomplete="tel" inputmode="numeric" maxlength="15" data-mask="telefone">
                                        </div>
                                    </div>
                                </section>

                                <details class="optional-section p-7 md:p-9" id="schedule-section" open>
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
                                    <fieldset class="m-0 p-0 border-0">
                                        <legend class="visually-hidden">Dados de agendamento</legend>
                                        <div class="row g-5">
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="week-day">Dia da semana</label>
                                                <select class="form-control" id="week-day" name="dia_semana">
                                                    <option value="">Selecione o dia</option>
                                                    <option value="1" <?= (string) ($agendamento['dia_semana'] ?? '') === '1' ? 'selected' : '' ?>>Segunda-feira</option>
                                                    <option value="2" <?= (string) ($agendamento['dia_semana'] ?? '') === '2' ? 'selected' : '' ?>>Terça-feira</option>
                                                    <option value="3" <?= (string) ($agendamento['dia_semana'] ?? '') === '3' ? 'selected' : '' ?>>Quarta-feira</option>
                                                    <option value="4" <?= (string) ($agendamento['dia_semana'] ?? '') === '4' ? 'selected' : '' ?>>Quinta-feira</option>
                                                    <option value="5" <?= (string) ($agendamento['dia_semana'] ?? '') === '5' ? 'selected' : '' ?>>Sexta-feira</option>
                                                    <option value="6" <?= (string) ($agendamento['dia_semana'] ?? '') === '6' ? 'selected' : '' ?>>Sábado</option>
                                                    <option value="0" <?= (string) ($agendamento['dia_semana'] ?? '') === '0' ? 'selected' : '' ?>>Domingo</option>
                                                </select>
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="repeat-interval">Repetição</label>
                                                <select class="form-control" id="repeat-interval" name="repeticao">
                                                    <option value="">Selecione a frequência</option>
                                                    <option value="Semanal" <?= ($agendamento['repeticao'] ?? '') === 'Semanal' ? 'selected' : '' ?>>Semanal</option>
                                                    <option value="Quinzenal" <?= ($agendamento['repeticao'] ?? '') === 'Quinzenal' ? 'selected' : '' ?>>Quinzenal</option>
                                                    <option value="Mensal" <?= ($agendamento['repeticao'] ?? '') === 'Mensal' ? 'selected' : '' ?>>Mensal</option>
                                                </select>
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="start-time">Horário de início</label>
                                                <input class="form-control" type="time" id="start-time" name="horario_inicio" value="<?= htmlspecialchars(substr($agendamento['horario_inicio'] ?? '', 0, 5), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="end-time">Horário de término</label>
                                                <input class="form-control" type="time" id="end-time" name="horario_termino" value="<?= htmlspecialchars(substr($agendamento['horario_termino'] ?? '', 0, 5), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="start-date">Data de início</label>
                                                <input class="form-control" type="date" id="start-date" name="data_inicio" value="<?= htmlspecialchars(substr($agendamento['data_inicio'] ?? '', 0, 10), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </fieldset>
                                </details>

                                <details class="optional-section p-7 md:p-9" id="profile-section" open>
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
                                    <fieldset class="m-0 p-0 border-0">
                                        <legend class="visually-hidden">Perfil do paciente</legend>
                                        <div class="mb-5">
                                            <label class="form-label text-sm" for="patient-condition">Quadro</label>
                                            <input class="form-control" type="text" id="patient-condition" name="quadro" value="<?= htmlspecialchars($perfil['quadro'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" placeholder="Informe o quadro do paciente">
                                        </div>
                                        <div>
                                            <label class="form-label text-sm" for="patient-description">Descrição</label>
                                            <textarea class="form-control" id="patient-description" name="descricao" rows="5" placeholder="Descreva as informações relevantes para o acompanhamento."><?= htmlspecialchars($perfil['descricao'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                                        </div>
                                    </fieldset>
                                </details>

                                <details class="optional-section p-7 md:p-9" id="referral-section" open>
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
                                    <fieldset class="m-0 p-0 border-0">
                                        <legend class="visually-hidden">Encaminhamento</legend>
                                        <div class="row g-5">
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="referral-type">Tipo de encaminhamento</label>
                                                <select class="form-control" id="referral-type" name="tipo_encaminhamento">
                                                    <option value="">Selecione o tipo</option>
                                                    <option value="Recebimento" <?= ($encaminhamento['tipo_encaminhamento'] ?? '') === 'Recebimento' ? 'selected' : '' ?>>Recebimento</option>
                                                    <option value="Envio" <?= ($encaminhamento['tipo_encaminhamento'] ?? '') === 'Envio' ? 'selected' : '' ?>>Envio</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-sm" for="patient-history">Histórico do paciente</label>
                                                <textarea class="form-control" id="patient-history" name="historico_paciente" rows="4" placeholder="Registre as informações do histórico do paciente."><?= htmlspecialchars($encaminhamento['historico_paciente'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></textarea>
                                            </div>
                                        </div>
                                    </fieldset>
                                </details>
                            </div>

                        </fieldset>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-5 mt-7">
                            <small style="color: var(--color-text-muted);">* Campo obrigatório</small>
                            <button class="btn-solid px-7 py-4" type="submit">
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                Salvar alterações
                            </button>
                        </div>
                    </form>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>

    <script id="patients-data" type="application/json"><?= json_encode($pacientesSelecao ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
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
