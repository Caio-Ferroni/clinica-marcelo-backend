/*
 * Carregar bootstrap.min.css, style.css e este script com defer.
 * O CSS do Bootstrap Icons é carregado automaticamente, se necessário.
 * COMO FAZER (classes do Bootstrap):
 * <div class="d-flex flex-column md:flex-row min-vh-100">
 *   <clinic-sidebar active="patients"></clinic-sidebar>
 *   <div class="d-flex flex-column flex-grow-1 min-w-0">
 *     <main class="flex-grow-1"></main>
 *     <clinic-footer></clinic-footer>
 *   </div>
 * </div>
 * Seções: home, patients, appointments, reports, tasks, profile.
 */
(() => {
    if (!document.querySelector('link[href*="bootstrap-icons"]')) {
        const stylesheet = document.createElement('link');
        stylesheet.rel = 'stylesheet';
        stylesheet.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
        document.head.appendChild(stylesheet);
    }

    function renderMenuItem(section, label, icon) {
        return `
            <li>
                <a href="#"
                    class="d-flex align-items-center gap-4 w-100 p-4 rounded-3 text-start text-decoration-none"
                    data-section="${section}"
                    style="border: 0; background: transparent; color: inherit; font: inherit;">
                    <i class="bi bi-${icon} text-lg" aria-hidden="true"></i>
                    ${label}
                </a>
            </li>`;
    }

    class ClinicSidebar extends HTMLElement {
        static get observedAttributes() {
            return ['active'];
        }

        connectedCallback() {
            this.classList.add('d-flex', 'col-12', 'md:col-3', 'xl:col-2', 'flex-shrink-0');
            this.innerHTML = `
                <aside class="d-flex flex-column w-100 p-7 text-sm" aria-label="Menu da clínica" style="background: var(--color-dark-blue); color: var(--color-white);">
                    <div class="d-flex align-items-center gap-4 py-5" style="--bs-border-color: var(--color-light-blue);">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" aria-hidden="true" style="width: 42px; height: 42px; background: var(--color-light-blue); color: var(--color-dark-blue);">
                            <i class="bi bi-person text-lg"></i>
                        </span>
                        <div>
                            <small class="d-block" style="color: var(--color-light-blue);">Bem-vindo</small>
                            <strong>Olá, Marcelo!</strong>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-5 mb-7">
                        <a href="#" class="d-inline-flex align-items-center gap-2 p-0 text-xs text-decoration-none" style="color: var(--color-light-blue);">
                            <i class="bi bi-bell text-lg" aria-hidden="true"></i>
                            Notificações
                        </a>
                        <a href="#" data-open-tutorial aria-haspopup="dialog" aria-controls="tutorial-modal" class="d-inline-flex align-items-center gap-2 p-0 text-xs text-decoration-none" style="color: var(--color-light-blue);">
                            <i class="bi bi-play-circle text-lg" aria-hidden="true"></i>
                            Ajuda
                        </a>
                    </div>
                    <nav aria-label="Navegação principal" class="mb-7">
                        <p class="text-xs mb-4" style="color: var(--color-light-blue);">MENU PRINCIPAL</p>
                        <ul class="list-unstyled d-flex flex-wrap md:flex-column gap-2 mb-0">
                            ${renderMenuItem('home', 'Início', 'house-door')}
                            ${renderMenuItem('patients', 'Pacientes', 'people')}
                            ${renderMenuItem('appointments', 'Consultas', 'calendar-event')}
                            ${renderMenuItem('reports', 'Relatórios', 'file-earmark-text')}
                            ${renderMenuItem('tasks', 'Tarefas', 'clipboard-check')}
                        </ul>
                    </nav>
                    <nav aria-label="Conta" class="mt-auto pt-5 border-top" style="--bs-border-color: var(--color-light-blue);">
                        <ul class="list-unstyled d-flex flex-wrap md:flex-column gap-2 mb-0">
                            ${renderMenuItem('profile', 'Meu perfil', 'person')}
                            ${renderMenuItem('website', 'Home', 'house-door')}
                            ${renderMenuItem('logout', 'Sair', 'box-arrow-right')}
                        </ul>
                    </nav>
                </aside>`;
            this.updateActive();
        }

        attributeChangedCallback() {
            this.updateActive();
        }

        updateActive() {
            const activeSection = this.getAttribute('active');

            this.querySelectorAll('[data-section]').forEach((link) => {
                const isActive = link.dataset.section === activeSection;

                link.style.background = isActive ? 'var(--color-light-blue)' : 'transparent';
                link.style.color = isActive ? 'var(--color-dark-blue)' : 'inherit';
                link.classList.toggle('fw-semibold', isActive);

                if (isActive) {
                    link.setAttribute('aria-current', 'page');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        }
    }

    class ClinicFooter extends HTMLElement {
        connectedCallback() {
            this.classList.add('d-block', 'mt-auto');
            this.innerHTML = `
                <footer class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-top p-5 text-xs" style="background: var(--color-white); color: var(--color-dark-blue); --bs-border-color: var(--color-light-blue);">
                    <span>Footer <span aria-hidden="true">·</span> A Fazer</span>
                </footer>`;
        }
    }

    if (!customElements.get('clinic-sidebar')) {
        customElements.define('clinic-sidebar', ClinicSidebar);
    }

    if (!customElements.get('clinic-footer')) {
        customElements.define('clinic-footer', ClinicFooter);
    }

    document.body.insertAdjacentHTML('beforeend', `
        <dialog id="tutorial-modal" class="clinic-modal" aria-labelledby="tutorial-title">
            <div class="p-7">
                <div class="d-flex align-items-center justify-content-between gap-4 mb-7">
                    <h2 id="tutorial-title" class="text-lg fw-semibold m-0">Vídeo tutorial</h2>
                    <button type="button" class="btn-text" data-close-modal aria-label="Fechar vídeo tutorial">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="ratio ratio-16x9 border rounded-3" style="background: var(--color-field-background);">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="bi bi-play-circle text-6xl" aria-hidden="true"></i>
                    </div>
                </div>
                <p class="text-center text-sm mt-5 mb-0">Vídeo tutorial em breve.</p>
            </div>
        </dialog>`);

    const tutorialModal = document.getElementById('tutorial-modal');
    document.querySelectorAll('[data-open-tutorial]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            tutorialModal.showModal();
        });
    });

    const editForm = document.querySelector('[data-patient-edit]');

    if (editForm) {
        document.body.insertAdjacentHTML('beforeend', `
            <dialog id="patient-search-modal" class="clinic-modal patient-search-modal" aria-labelledby="patient-search-title">
                <div class="p-7">
                    <div class="d-flex align-items-center justify-content-between gap-4 mb-5">
                        <h2 id="patient-search-title" class="text-lg fw-semibold m-0">Selecionar paciente</h2>
                        <button type="button" class="btn-text" data-close-modal aria-label="Fechar pesquisa de paciente">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <label class="form-label text-sm" for="patient-search">Nome ou CPF</label>
                    <div class="input-group mb-5">
                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="search" class="form-control" id="patient-search" placeholder="Pesquisar paciente" autocomplete="off">
                    </div>
                    <p id="patient-search-message" class="text-sm" role="status"></p>
                    <ul id="patient-search-results" class="patient-search-results list-unstyled m-0"></ul>
                </div>
            </dialog>`);

        const searchModal = document.getElementById('patient-search-modal');
        const searchInput = document.getElementById('patient-search');
        const results = document.getElementById('patient-search-results');
        const message = document.getElementById('patient-search-message');

        function normalizeText(value) {
            return String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        }

        function selectPatient(patient) {
            editForm.querySelector('[data-patient-fields]').disabled = false;
            editForm.querySelectorAll('[name]').forEach((field) => {
                field.value = field.name === 'patient_id' ? patient.id : (patient[field.name] ?? '');
            });
            editForm.querySelector('[type="submit"]').disabled = false;
            document.getElementById('save-message').hidden = true;
            searchModal.close();
            editForm.querySelector('[name="full_name"]').focus();
        }

        function searchPatients() {
            results.replaceChildren();
            let patients;

            try {
                patients = JSON.parse(document.getElementById('patients-data').textContent);
                if (!Array.isArray(patients)) throw new Error();
            } catch {
                message.textContent = 'Não foi possível carregar os pacientes.';
                return;
            }

            const term = normalizeText(searchInput.value);
            const cpf = searchInput.value.replace(/\D/g, '');
            const matches = patients.filter((patient) => {
                return patient && patient.id != null && patient.full_name && (
                    normalizeText(patient.full_name).includes(term) ||
                    (cpf && String(patient.cpf || '').replace(/\D/g, '').includes(cpf))
                );
            });

            message.textContent = patients.length === 0
                ? 'Nenhum paciente disponível para seleção.'
                : (matches.length ? `${matches.length} paciente(s) encontrado(s).` : 'Nenhum paciente encontrado.');

            matches.forEach((patient) => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn-text w-100 justify-content-start text-start border-bottom rounded-0 p-5';
                button.textContent = patient.full_name;
                button.addEventListener('click', () => selectPatient(patient));
                item.appendChild(button);
                results.appendChild(item);
            });
        }

        document.querySelectorAll('[data-open-patient-search]').forEach((button) => {
            button.addEventListener('click', () => {
                searchInput.value = '';
                searchPatients();
                searchModal.showModal();
                searchInput.focus();
            });
        });

        searchInput.addEventListener('input', searchPatients);
    }

    document.querySelectorAll('.clinic-modal').forEach((modal) => {
        modal.querySelector('[data-close-modal]').addEventListener('click', () => modal.close());
        modal.addEventListener('click', (event) => {
            const bounds = modal.getBoundingClientRect();
            if (event.target === modal && (
                event.clientX < bounds.left || event.clientX > bounds.right ||
                event.clientY < bounds.top || event.clientY > bounds.bottom
            )) {
                modal.close();
            }
        });
    });

    document.querySelectorAll('table').forEach((table) => {
        const body = table.tBodies[0];
        if (!body) return;

        const pageSize = Math.max(1, parseInt(table.dataset.pageSize, 10) || 10);
        let currentPage = 1;
        const emptyRow = body.insertRow();
        emptyRow.dataset.emptyRow = '';
        const emptyCell = emptyRow.insertCell();
        emptyCell.colSpan = table.tHead?.rows[0]?.cells.length || 1;
        emptyCell.className = 'text-center p-7';
        emptyCell.textContent = 'Nenhum resultado disponível.';

        const navigation = document.createElement('nav');
        navigation.className = 'd-flex flex-wrap align-items-center justify-content-between gap-4 mt-5';
        navigation.setAttribute('aria-label', 'Paginação dos resultados');
        navigation.innerHTML = `
            <span class="text-sm" data-result-count role="status"></span>
            <div class="d-flex align-items-center gap-4">
                <button type="button" class="btn-outline" data-previous-page>Anterior</button>
                <span class="text-sm" data-page-number></span>
                <button type="button" class="btn-outline" data-next-page>Próxima</button>
            </div>`;
        (table.closest('.table-responsive') || table).after(navigation);

        const previous = navigation.querySelector('[data-previous-page]');
        const next = navigation.querySelector('[data-next-page]');

        function showPage() {
            if (emptyRow.parentElement !== body) body.appendChild(emptyRow);
            const rows = Array.from(body.rows).filter((row) => !row.hasAttribute('data-empty-row'));
            const totalPages = Math.ceil(rows.length / pageSize);
            currentPage = Math.max(1, Math.min(currentPage, totalPages));
            const start = (currentPage - 1) * pageSize;

            rows.forEach((row, index) => {
                row.hidden = index < start || index >= start + pageSize;
            });

            emptyRow.hidden = rows.length > 0;
            navigation.querySelector('[data-result-count]').textContent = rows.length
                ? `Mostrando ${start + 1} a ${Math.min(start + pageSize, rows.length)} de ${rows.length} resultados`
                : 'Nenhum resultado';
            navigation.querySelector('[data-page-number]').textContent = `Página ${totalPages ? currentPage : 0} de ${totalPages}`;
            previous.disabled = currentPage === 1;
            next.disabled = currentPage >= totalPages;
        }

        previous.addEventListener('click', () => {
            currentPage--;
            showPage();
        });
        next.addEventListener('click', () => {
            currentPage++;
            showPage();
        });

        showPage();
        new MutationObserver(showPage).observe(body, { childList: true });
    });
})();
