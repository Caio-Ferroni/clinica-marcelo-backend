<?php
$paciente = $paciente ?? [];
$perfil = $perfil ?? [];
$agendamento = $agendamento ?? [];
$encaminhamento = $encaminhamento ?? [];
$escape = static fn($valor) => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$dias = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$diaSelecionado = $dias[(string) ($agendamento['dia_semana'] ?? '')] ?? '';
$repeticoes = ['semanal' => 'weekly', 'quinzenal' => 'fortnightly', 'mensal' => 'monthly'];
$repeticaoSelecionada = $repeticoes[strtolower($agendamento['repeticao'] ?? '')] ?? '';
$status = ['ativo' => 'active', 'inativo' => 'inactive', 'active' => 'active', 'inactive' => 'inactive'];
$statusSelecionado = $status[strtolower($paciente['status'] ?? '')] ?? '';
$tipos = ['recebimento' => 'received', 'envio' => 'sent', 'received' => 'received', 'sent' => 'sent'];
$tipoSelecionado = $tipos[strtolower($encaminhamento['tipo_encaminhamento'] ?? '')] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar paciente | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
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
                        <button type="button" class="btn-outline mt-5" data-open-patient-search aria-haspopup="dialog" aria-controls="patient-search-modal">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            Selecionar paciente
                        </button>
                    </header>

                    <form id="patient-form" data-patient-edit>
                        <input type="hidden" name="patient_id" value="<?= $escape($paciente['id_paciente'] ?? '') ?>">
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
                                            <input class="form-control" type="text" id="patient-name" name="full_name" value="<?= $escape($paciente['nome'] ?? '') ?>" placeholder="Selecione um paciente" autocomplete="name" required>
                                        </div>
                                        <div class="col-12 md:col-3">
                                            <label class="form-label text-sm" for="birth-date">Data de nascimento</label>
                                            <input class="form-control" type="date" id="birth-date" name="birth_date" value="<?= $escape(substr($paciente['data_nascimento'] ?? '', 0, 10)) ?>" autocomplete="bday">
                                        </div>
                                        <div class="col-12 md:col-3">
                                            <label class="form-label text-sm" for="patient-status">Status</label>
                                            <select class="form-control" id="patient-status" name="status">
                                                <option value="">Selecione</option>
                                                <option value="active" <?= $statusSelecionado === 'active' ? 'selected' : '' ?>>Ativo</option>
                                                <option value="inactive" <?= $statusSelecionado === 'inactive' ? 'selected' : '' ?>>Inativo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="patient-cpf">CPF</label>
                                            <input class="form-control" type="text" id="patient-cpf" name="cpf" value="<?= $escape($paciente['cpf'] ?? '') ?>" placeholder="000.000.000-00" inputmode="numeric" maxlength="14">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="patient-phone">Telefone</label>
                                            <input class="form-control" type="tel" id="patient-phone" name="phone" value="<?= $escape($paciente['telefone'] ?? '') ?>" placeholder="(00) 00000-0000" autocomplete="tel">
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
                                                <select class="form-control" id="week-day" name="week_day">
                                                    <option value="">Selecione o dia</option>
                                                    <option value="monday" <?= $diaSelecionado === 'monday' ? 'selected' : '' ?>>Segunda-feira</option>
                                                    <option value="tuesday" <?= $diaSelecionado === 'tuesday' ? 'selected' : '' ?>>Terça-feira</option>
                                                    <option value="wednesday" <?= $diaSelecionado === 'wednesday' ? 'selected' : '' ?>>Quarta-feira</option>
                                                    <option value="thursday" <?= $diaSelecionado === 'thursday' ? 'selected' : '' ?>>Quinta-feira</option>
                                                    <option value="friday" <?= $diaSelecionado === 'friday' ? 'selected' : '' ?>>Sexta-feira</option>
                                                    <option value="saturday" <?= $diaSelecionado === 'saturday' ? 'selected' : '' ?>>Sábado</option>
                                                    <option value="sunday" <?= $diaSelecionado === 'sunday' ? 'selected' : '' ?>>Domingo</option>
                                                </select>
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="repeat-interval">Repetição</label>
                                                <select class="form-control" id="repeat-interval" name="repeat_interval">
                                                    <option value="">Selecione a frequência</option>
                                                    <option value="weekly" <?= $repeticaoSelecionada === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                                    <option value="fortnightly" <?= $repeticaoSelecionada === 'fortnightly' ? 'selected' : '' ?>>Quinzenal</option>
                                                    <option value="monthly" <?= $repeticaoSelecionada === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                                                </select>
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="start-time">Horário de início</label>
                                                <input class="form-control" type="time" id="start-time" name="start_time" value="<?= $escape(substr($agendamento['horario_inicio'] ?? '', 0, 5)) ?>">
                                            </div>
                                            <div class="col-12 md:col-6">
                                                <label class="form-label text-sm" for="start-date">Data de início</label>
                                                <input class="form-control" type="date" id="start-date" name="start_date" value="<?= $escape(substr($agendamento['data_inicio'] ?? '', 0, 10)) ?>">
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
                                            <input class="form-control" type="text" id="patient-condition" name="clinical_condition" value="<?= $escape($perfil['quadro'] ?? '') ?>" placeholder="Informe o quadro do paciente">
                                        </div>
                                        <div>
                                            <label class="form-label text-sm" for="patient-description">Descrição</label>
                                            <textarea class="form-control" id="patient-description" name="description" rows="5" placeholder="Descreva as informações relevantes para o acompanhamento."><?= $escape($perfil['descricao'] ?? '') ?></textarea>
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
                                                <select class="form-control" id="referral-type" name="referral_type">
                                                    <option value="">Selecione o tipo</option>
                                                    <option value="received" <?= $tipoSelecionado === 'received' ? 'selected' : '' ?>>Recebimento</option>
                                                    <option value="sent" <?= $tipoSelecionado === 'sent' ? 'selected' : '' ?>>Envio</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-sm" for="patient-history">Histórico do paciente</label>
                                                <textarea class="form-control" id="patient-history" name="history" rows="4" placeholder="Registre as informações do histórico do paciente."><?= $escape($encaminhamento['historico_paciente'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </fieldset>
                                </details>
                            </div>

                        </fieldset>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-5 mt-7">
                            <small style="color: var(--color-text-muted);">* Campo obrigatório</small>
                            <button class="btn-solid px-7 py-4" type="submit" disabled>
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                                Salvar alterações
                            </button>
                        </div>
                        <p id="save-message" class="border rounded-3 p-5 mt-5 mb-0" role="status" hidden style="background: var(--color-light-blue); color: var(--color-dark-blue);">O salvamento ainda não está disponível. Nenhuma alteração foi salva.</p>
                    </form>
                </div>
            </main>
            <clinic-footer></clinic-footer>
        </div>
    </div>

    <script id="patients-data" type="application/json">[]</script>
    <script>
        document.querySelectorAll('.optional-section').forEach((section) => {
            section.addEventListener('toggle', () => {
                section.querySelector('fieldset').disabled = !section.open;
            });
        });

        document.getElementById('patient-form').addEventListener('submit', (event) => {
            event.preventDefault();
            document.getElementById('save-message').hidden = false;
        });
    </script>
</body>
</html>
