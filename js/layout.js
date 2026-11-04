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
                        <a href="#" class="d-inline-flex align-items-center gap-2 p-0 text-xs text-decoration-none" style="color: var(--color-light-blue);">
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
})();