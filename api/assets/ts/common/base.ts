import '../../styles/app.css';

import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import { Polish } from "flatpickr/dist/l10n/pl.js";

flatpickr.localize(Polish);

import 'bootstrap';
import "bootstrap/dist/css/bootstrap.min.css";
import {Modal, Alert} from "bootstrap";

export interface BootstrapModalEvent extends Event {
    relatedTarget?: HTMLElement | null;
}

export interface ModalFeatureConfig {
    containerId: string;
    modalId: string;
    newBtnId: string;
    newUrl: string;
    onLoaded?: () => void;
}

interface DeleteModalConfig {
    modalId?: string;
}

export interface AutoHideAlertsConfig {
    delay?: number;
}

export class AppBase {

    static initModalFeature({ containerId, modalId, newBtnId, newUrl, onLoaded }: ModalFeatureConfig): void {
        const container = document.getElementById(containerId);

        if (!container) {
            return;
        }

        let currentModal: Modal | null = null;

        function openModal(html: string): void {
            if (!container) return;

            if (currentModal) {
                currentModal.hide();
                currentModal.dispose();
                container.innerHTML = '';
            }

            container.innerHTML = html;

            container.querySelectorAll<HTMLInputElement>('.flatpickr').forEach(input => {
                flatpickr(input, {
                    dateFormat: "Y-m-d",
                    locale: "pl",
                    defaultDate: input.value || "today",
                    allowInput: true,
                });
            });

            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;

            currentModal = new Modal(modalEl, { focus: true });

            modalEl.addEventListener('hide.bs.modal', () => {
                if (document.activeElement && modalEl.contains(document.activeElement as Node)) {
                    (document.activeElement as HTMLElement).blur();
                }
            });

            modalEl.addEventListener('hidden.bs.modal', () => {
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

        document.querySelectorAll<HTMLElement>('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                if (url) {
                    fetch(url)
                        .then(res => res.text())
                        .then(openModal);
                }
            });
        });
    }

    static initDeleteModal({ modalId = 'confirmDeleteModal' }: DeleteModalConfig = {}): void {
        const deleteModal = document.getElementById(modalId);
        if (!deleteModal) {
            return;
        }

        deleteModal.addEventListener('show.bs.modal', (event: Event) => {
            const customEvent = event as BootstrapModalEvent;
            const button = customEvent.relatedTarget;

            if (!button) {
                return;
            }

            const deleteUrl = button.getAttribute('data-delete-url');
            const itemName = button.getAttribute('data-item-name');
            const token = button.getAttribute('data-token');

            const itemNameEl = deleteModal.querySelector('#delete-item-name');
            if (itemNameEl && itemName) {
                itemNameEl.textContent = itemName;
            }

            const form = deleteModal.querySelector<HTMLFormElement>('#delete-form');
            const tokenInput = form?.querySelector<HTMLInputElement>('#delete-token');

            if (form && deleteUrl) {
                form.action = deleteUrl;
            }
            if (tokenInput && token) {
                tokenInput.value = token;
            }
        });
    }

    static initUniversalModalFocusHandler(): void {
        document.addEventListener('hide.bs.modal', function (e: Event) {
            const modal = e.target as HTMLElement;
            if (modal?.classList.contains('modal')) {
                if (document.activeElement && modal.contains(document.activeElement as Node)) {
                    (document.activeElement as HTMLElement).blur();
                }
            }
        });
    }

    static initAutoHideAlerts({ delay = 5000 }: AutoHideAlertsConfig = {}): void {
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                const bsAlert = new Alert(alert);
                bsAlert.close();
            }, delay);
        });
    }

    static initSportSwitcher(): void {
        const switcher = document.getElementById('sport-switcher') as HTMLSelectElement;
        if (!switcher) return;

        switcher.addEventListener('change', async (e: Event) => {
            const target = e.target as HTMLSelectElement;
            const sportId = target.value;
            if (!sportId) return;

            try {
                await fetch(`/sports/set/${sportId}`, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const cleanUrl = window.location.origin + window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
                window.location.reload();
            } catch (e) {
                console.error('Failed to switch sport', e);
            }
        });
    }
}
