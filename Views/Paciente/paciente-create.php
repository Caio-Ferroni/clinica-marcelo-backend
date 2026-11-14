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
                                        <input class="form-control" type="text" id="patient-name" name="full_name" placeholder="Digite o nome do paciente" autocomplete="name" required>
                                    </div>
                                    <div class="col-12 md:col-4">
                                        <label class="form-label text-sm" for="birth-date">Data de nascimento</label>
                                        <input class="form-control" type="date" id="birth-date" name="birth_date" autocomplete="bday">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-cpf">CPF</label>
                                        <input class="form-control" type="text" id="patient-cpf" name="cpf" placeholder="000.000.000-00" inputmode="numeric" maxlength="14">
                                    </div>
                                    <div class="col-12 md:col-6">
                                        <label class="form-label text-sm" for="patient-phone">Telefone</label>
                                        <input class="form-control" type="tel" id="patient-phone" name="phone" placeholder="(00) 00000-0000" autocomplete="tel">
                                    </div>
                                </div>
                            </section>

                            <details class="optional-section p-7 md:p-9" id="schedule-section">
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
                                <fieldset class="m-0 p-0 border-0" disabled>
                                    <legend class="visually-hidden">Dados de agendamento</legend>
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="week-day">Dia da semana</label>
                                            <select class="form-control" id="week-day" name="week_day">
                                                <option value="">Selecione o dia</option>
                                                <option value="monday">Segunda-feira</option>
                                                <option value="tuesday">Terça-feira</option>
                                                <option value="wednesday">Quarta-feira</option>
                                                <option value="thursday">Quinta-feira</option>
                                                <option value="friday">Sexta-feira</option>
                                                <option value="saturday">Sábado</option>
                                                <option value="sunday">Domingo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="repeat-interval">Repetição</label>
                                            <select class="form-control" id="repeat-interval" name="repeat_interval">
                                                <option value="">Selecione a frequência</option>
                                                <option value="weekly">Semanal</option>
                                                <option value="fortnightly">Quinzenal</option>
                                                <option value="monthly">Mensal</option>
                                            </select>
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-time">Horário de início</label>
                                            <input class="form-control" type="time" id="start-time" name="start_time">
                                        </div>
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="start-date">Data de início</label>
                                            <input class="form-control" type="date" id="start-date" name="start_date">
                                        </div>
                                    </div>
                                </fieldset>
                            </details>

                            <details class="optional-section p-7 md:p-9" id="profile-section">
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
                                <fieldset class="m-0 p-0 border-0" disabled>
                                    <legend class="visually-hidden">Perfil do paciente</legend>
                                    <div class="mb-5">
                                        <label class="form-label text-sm" for="patient-condition">Quadro</label>
                                        <input class="form-control" type="text" id="patient-condition" name="clinical_condition" placeholder="Informe o quadro do paciente">
                                    </div>
                                    <div>
                                        <label class="form-label text-sm" for="patient-description">Descrição</label>
                                        <textarea class="form-control" id="patient-description" name="description" rows="5" placeholder="Descreva as informações relevantes para o acompanhamento."></textarea>
                                    </div>
                                </fieldset>
                            </details>

                            <details class="optional-section p-7 md:p-9" id="referral-section">
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
                                <fieldset class="m-0 p-0 border-0" disabled>
                                    <legend class="visually-hidden">Encaminhamento</legend>
                                    <div class="row g-5">
                                        <div class="col-12 md:col-6">
                                            <label class="form-label text-sm" for="referral-type">Tipo de encaminhamento</label>
                                            <select class="form-control" id="referral-type" name="referral_type">
                                                <option value="">Selecione o tipo</option>
                                                <option value="received">Recebimento</option>
                                                <option value="sent">Envio</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-sm" for="patient-history">Histórico do paciente</label>
                                            <textarea class="form-control" id="patient-history" name="history" rows="4" placeholder="Registre as informações do histórico do paciente."></textarea>
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