<?php
$valores = $valores ?? [];
$valor = static function ($campo, $alias = '') use ($valores) {
    $conteudo = $valores[$campo] ?? $valores[$alias] ?? '';
    return is_scalar($conteudo) ? (string) $conteudo : '';
};
$escape = static fn($conteudo) => htmlspecialchars((string) $conteudo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$dias = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$repeticoes = ['semanal' => 'weekly', 'quinzenal' => 'fortnightly', 'mensal' => 'monthly'];
$tipos = ['recebimento' => 'received', 'envio' => 'sent', 'received' => 'received', 'sent' => 'sent'];
$dados = [
    'full_name' => $valor('nome', 'full_name'),
    'birth_date' => $valor('data_nascimento', 'birth_date'),
    'cpf' => $valor('cpf'),
    'phone' => $valor('telefone', 'phone'),
    'week_day' => $dias[$valor('dia_semana')] ?? $valor('week_day'),
    'repeat_interval' => $repeticoes[strtolower($valor('repeticao'))] ?? $valor('repeat_interval'),
    'start_time' => $valor('horario_inicio', 'start_time'),
    'start_date' => $valor('data_inicio', 'start_date'),
    'clinical_condition' => $valor('quadro', 'clinical_condition'),
    'description' => $valor('descricao', 'description'),
    'referral_type' => $tipos[strtolower($valor('tipo_encaminhamento'))] ?? $valor('referral_type'),
    'history' => $valor('historico_paciente', 'history')
];
$abrirAgendamento = $dados['week_day'] !== '' || $dados['repeat_interval'] !== '' || $dados['start_time'] !== '' || $dados['start_date'] !== '' || $valor('horario_termino', 'horario_fim') !== '';
$abrirPerfil = $dados['clinical_condition'] !== '' || $dados['description'] !== '';
$abrirEncaminhamento = $dados['referral_type'] !== '' || $dados['history'] !== '';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar paciente | Clínica</title>
    <link rel="stylesheet" href="../../css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/layout.js" defer></script>
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
                        <p class="border rounded-3 p-5 mb-5" role="alert" style="background: var(--color-light-blue);"><?= $escape($erro) ?></p>
                    <?php endif; ?>

                    <form id="patient-form">
                        <div class="border rounded-4 shadow-sm" style="background: var(--color-surface);">
                            <section class="p-7 md:p-9" aria-labelledby="personal-heading">
                                <h2 id="personal-heading" class="text-md fw-semibold d-flex align-items-center gap-3 mb-7">
                                    <i class="bi bi-person" aria-hidden="true"></i>
                                    Dados pessoais
                                </h2>
                                <div class="row g-5">
                                    <div class="col-12 md:col-8">
                                        <label class="form-label text-sm" for="patient-name">Nome completo <span aria-hidden="true">*</span></label>
                                        <input class="form-control" type="text" id="patient-name" name="full_name" value="<?= $escape($dados['full_name']) ?>" placeholder="Digite o nome do paciente" autocomplete="name" required>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <label class="form-label text-sm" for="birth-date">Data de nascimento</label>
                                        <input class="form-control" type="date" id="birth-date" name="birth_date" value="<?= $escape($dados['birth_date']) ?>" autocomplete="bday">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-cpf">CPF</label>
                                        <input class="form-control" type="text" id="patient-cpf" name="cpf" value="<?= $escape($dados['cpf']) ?>" placeholder="000.000.000-00" inputmode="numeric" maxlength="14">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-phone">Telefone</label>
                                        <input class="form-control" type="tel" id="patient-phone" name="phone" value="<?= $escape($dados['phone']) ?>" placeholder="(00) 00000-0000" autocomplete="tel">
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
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="week-day">Dia da semana</label>
                                            <select class="form-control" id="week-day" name="week_day">
                                                <option value="">Selecione o dia</option>
                                                <option value="monday" <?= $dados['week_day'] === 'monday' ? 'selected' : '' ?>>Segunda-feira</option>
                                                <option value="tuesday" <?= $dados['week_day'] === 'tuesday' ? 'selected' : '' ?>>Terça-feira</option>
                                                <option value="wednesday" <?= $dados['week_day'] === 'wednesday' ? 'selected' : '' ?>>Quarta-feira</option>
                                                <option value="thursday" <?= $dados['week_day'] === 'thursday' ? 'selected' : '' ?>>Quinta-feira</option>
                                                <option value="friday" <?= $dados['week_day'] === 'friday' ? 'selected' : '' ?>>Sexta-feira</option>
                                                <option value="saturday" <?= $dados['week_day'] === 'saturday' ? 'selected' : '' ?>>Sábado</option>
                                                <option value="sunday" <?= $dados['week_day'] === 'sunday' ? 'selected' : '' ?>>Domingo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="repeat-interval">Repetição</label>
                                            <select class="form-control" id="repeat-interval" name="repeat_interval">
                                                <option value="">Selecione a frequência</option>
                                                <option value="weekly" <?= $dados['repeat_interval'] === 'weekly' ? 'selected' : '' ?>>Semanal</option>
                                                <option value="fortnightly" <?= $dados['repeat_interval'] === 'fortnightly' ? 'selected' : '' ?>>Quinzenal</option>
                                                <option value="monthly" <?= $dados['repeat_interval'] === 'monthly' ? 'selected' : '' ?>>Mensal</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-time">Horário de início</label>
                                            <input class="form-control" type="time" id="start-time" name="start_time" value="<?= $escape($dados['start_time']) ?>">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-date">Data de início</label>
                                            <input class="form-control" type="date" id="start-date" name="start_date" value="<?= $escape($dados['start_date']) ?>">
                                        </div>
                                    </div>
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
                                        <input class="form-control" type="text" id="patient-condition" name="clinical_condition" value="<?= $escape($dados['clinical_condition']) ?>" placeholder="Informe o quadro do paciente">
                                    </div>
                                    <div>
                                        <label class="form-label text-sm" for="patient-description">Descrição</label>
                                        <textarea class="form-control" id="patient-description" name="description" rows="5" placeholder="Descreva as informações relevantes para o acompanhamento."><?= $escape($dados['description']) ?></textarea>
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
                                            <select class="form-control" id="referral-type" name="referral_type">
                                                <option value="">Selecione o tipo</option>
                                                <option value="received" <?= $dados['referral_type'] === 'received' ? 'selected' : '' ?>>Recebimento</option>
                                                <option value="sent" <?= $dados['referral_type'] === 'sent' ? 'selected' : '' ?>>Envio</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-sm" for="patient-history">Histórico do paciente</label>
                                            <textarea class="form-control" id="patient-history" name="history" rows="4" placeholder="Registre as informações do histórico do paciente."><?= $escape($dados['history']) ?></textarea>
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
                        <p id="save-message" class="border rounded-3 p-5 mt-5 mb-0" role="status" hidden style="background: var(--color-light-blue); color: var(--color-dark-blue);">O cadastro ainda não está disponível. Nenhum dado foi salvo.</p>
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

        document.getElementById('patient-form').addEventListener('submit', (event) => {
            event.preventDefault();
            document.getElementById('save-message').hidden = false;
        });
    </script>
</body>
</html>
