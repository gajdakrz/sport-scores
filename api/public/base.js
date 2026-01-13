/* global flatpickr */
window.AppBase = {
    initModalFeature({ containerId, modalId, newBtnId, newUrl, onLoaded }) {
        const container = document.getElementById(containerId);
        if (!container) return;
        let currentModal = null;

        function openModal(html) {
            if (currentModal) {
                currentModal.hide();
                currentModal.dispose();
                container.innerHTML = '';
            }

            container.innerHTML = html;

            const flatpickrInputs = container.querySelectorAll('.flatpickr');
            flatpickrInputs.forEach(input => {
                flatpickr(input, {
                    dateFormat: "Y-m-d",
                    locale: "pl",
                    defaultDate: input.value || "today",
                    allowInput: true,
                });
            });

            const modalEl = document.getElementById(modalId);
            currentModal = new bootstrap.Modal(modalEl, {
                focus: true
            });

            // Usuwamy focus PRZED rozpoczęciem animacji zamykania
            modalEl.addEventListener('hide.bs.modal', function (e) {
                if (document.activeElement && modalEl.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            });

            // Clean up after modal is fully hidden
            modalEl.addEventListener('hidden.bs.modal', function () {
                container.innerHTML = '';
                currentModal = null;
            }, { once: true });

            if (typeof onLoaded === 'function') {
                onLoaded();
            }

            currentModal.show();
        }

        const newBtn = document.getElementById(newBtnId);
        if (newBtn) {
            newBtn.addEventListener('click', () => {
                fetch(newUrl)
                    .then(res => res.text())
                    .then(openModal);
            });
        }

        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                fetch(btn.dataset.url)
                    .then(res => res.text())
                    .then(openModal);
            });
        });
    },

    initDeleteModal({ modalId = 'confirmDeleteModal' } = {}) {
        const deleteModal = document.getElementById(modalId);
        if (!deleteModal) return;

        deleteModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            if (!button) return;

            const deleteUrl = button.getAttribute('data-delete-url');
            const itemName = button.getAttribute('data-item-name');
            const token = button.getAttribute('data-token');

            deleteModal.querySelector('#delete-item-name').textContent = itemName;
            const form = deleteModal.querySelector('#delete-form');
            form.action = deleteUrl;
            form.querySelector('#delete-token').value = token;
        });

        // Usuwamy focus przed zamknięciem
        deleteModal.addEventListener('hide.bs.modal', function (e) {
            if (document.activeElement && deleteModal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        });
    },

    // NOWA FUNKCJA: Uniwersalny handler dla wszystkich modali
    initUniversalModalFocusHandler() {
        // Delegacja eventów na document dla wszystkich modali
        document.addEventListener('hide.bs.modal', function (e) {
            const modal = e.target;
            if (modal && modal.classList.contains('modal')) {
                // Sprawdź czy jakiś element w modalu ma focus
                if (document.activeElement && modal.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
            }
        });
    },

    initAutoHideAlerts({ delay = 5000 } = {}) {
        const alerts = document.querySelectorAll('.alert');

        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, delay);
        });
    },

    initSportSwitcher() {
        const switcher = document.getElementById('sport-switcher');
        if (!switcher) {
            return;
        }

        switcher.addEventListener('change', async (e) => {
            const sportId = e.target.value;
            if (!sportId) {
                return;
            }

            try {
                await fetch(`/sports/set/${sportId}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                // usuń query string (?page=2 itd.)
                const cleanUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
                window.location.reload();
            } catch (e) {
                console.error('Failed to switch sport', e);
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', () => {
    AppBase.initSportSwitcher();
    AppBase.initAutoHideAlerts();
    AppBase.initDeleteModal();
    AppBase.initUniversalModalFocusHandler();

    document.querySelectorAll('.flatpickr').forEach(input => {
        flatpickr(input, {
            dateFormat: "Y-m-d",
            locale: "pl",
            defaultDate: input.value || null,
            allowInput: true
        });
    });
});

// Handler pagination in modal
document.addEventListener('click', async (event) => {
    // Sprawdź czy kliknięty element to link paginacji wewnątrz modala
    const link = event.target.closest('.modal .pagination a.page-link');
    if (!link) {
        return;
    }

    const modal = link.closest('.modal');
    if (!modal) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const body = modal.querySelector('#season-details-body');
    if (!body) {
        return;
    }

    try {
        const res = await fetch(link.href, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        body.innerHTML = await res.text();
    } catch (e) {
        console.error('Pagination fetch failed:', e);
    }
});
