/* global flatpickr */
window.AppBase = {
    initModalFeature({ containerId, modalId, newBtnId, newUrl }) {
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
                    defaultDate: "today"
                });
            });

            const modalEl = document.getElementById(modalId);
            currentModal = new bootstrap.Modal(modalEl);

            // Remove focus BEFORE Bootstrap tries to hide the modal
            modalEl.addEventListener('hide.bs.modal', function (e) {
                // Blur any focused element within the modal
                const focusedElement = modalEl.querySelector(':focus');
                if (focusedElement) {
                    focusedElement.blur();
                }
            });

            // Clean up after modal is fully hidden
            modalEl.addEventListener('hidden.bs.modal', function () {
                container.innerHTML = '';
                currentModal = null;
            }, { once: true });

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

        // Remove focus BEFORE Bootstrap tries to hide the modal
        deleteModal.addEventListener('hide.bs.modal', function () {
            // Blur any focused element within the modal
            const focusedElement = deleteModal.querySelector(':focus');
            if (focusedElement) {
                focusedElement.blur();
            }
        });
    }
};
