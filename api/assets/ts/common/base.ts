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

export type FlashType = 'success' | 'danger' | 'warning' | 'info';

const FLASH_TIME_MS = 5000;

export class AppBase {

    static initModalFeature({ containerId, modalId, newBtnId, newUrl, onLoaded }: ModalFeatureConfig): void {
        const container = document.getElementById(containerId);
        if (!container) return;

        let currentModal: Modal | null = null;
        let isLoading = false;

        function openModal(html: string): void {
            if (!container) return;

            if (currentModal) {
                const prevModalEl = container.querySelector(`#${modalId}`);
                prevModalEl?.addEventListener('hidden.bs.modal', () => {
                    currentModal?.dispose();
                    currentModal = null;
                    container.innerHTML = '';
                    renderModal(html);
                }, { once: true });

                currentModal.hide();

                return;
            }

            renderModal(html);
        }

        function renderModal(html: string): void {
            if (!container) return;

            container.innerHTML = html;

            container.querySelectorAll<HTMLInputElement>('.flatpickr').forEach(input => {
                const defaultAttr = input.dataset.defaultDate;
                flatpickr(input, {
                    dateFormat: "Y-m-d",
                    locale: "pl",
                    allowInput: true,
                    ...(defaultAttr ? { defaultDate: input.value || defaultAttr } : {})
                });
            });

            const modalEl = document.getElementById(modalId);
            if (!modalEl) return;

            // Tworzymy kontroler powiązany z tym konkretnym modelem
            const formAbortController = new AbortController();

            const form = container.querySelector<HTMLFormElement>('form');
            if (form) {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();

                    const res = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const contentType = res.headers.get('Content-Type') ?? '';

                    if (contentType.includes('application/json')) {
                        const json = await res.json();
                        if (json.success) {
                            const modalEl = document.getElementById(modalId);
                            modalEl?.addEventListener('hidden.bs.modal', () => {
                                AppBase.showFlashAfterReload('success', json.message ?? 'Saved successfully.');
                                window.location.href = json.redirect ?? window.location.href;
                            }, { once: true });
                            currentModal?.hide();
                        } else {
                            const errorDiv = container.querySelector('.modal-body');
                            if (errorDiv && json.errors) {
                                errorDiv.querySelectorAll('.alert-danger').forEach(el => el.remove());
                                json.errors.forEach((err: { message: string; field: string }) => {
                                    const alert = document.createElement('div');
                                    alert.className = 'alert alert-danger';
                                    alert.textContent = err.message;
                                    errorDiv.prepend(alert);
                                });
                            }
                        }
                    } else if (res.redirected) {
                        const modalEl = document.getElementById(modalId);
                        modalEl?.addEventListener('hidden.bs.modal', () => {
                            window.location.reload();
                        }, { once: true });
                        currentModal?.hide();
                    } else {
                        const responseHtml = await res.text();
                        openModal(responseHtml);
                    }
                }, { signal: formAbortController.signal });
            }

            currentModal = new Modal(modalEl, { focus: true });

            modalEl.addEventListener('hide.bs.modal', () => {
                if (document.activeElement && modalEl.contains(document.activeElement as Node)) {
                    (document.activeElement as HTMLElement).blur();
                }
            }, { once: true });

            modalEl.addEventListener('hidden.bs.modal', () => {
                formAbortController.abort(); // ← czyścimy listener przy zamknięciu
                container.innerHTML = '';
                currentModal = null;
                isLoading = false;
            }, { once: true });

            if (typeof onLoaded === 'function') {
                onLoaded();
            }

            currentModal.show();
        }

        const newBtn = document.getElementById(newBtnId);
        if (newBtn) {
            newBtn.addEventListener('click', () => {
                if (isLoading || currentModal) {
                    return;
                }
                isLoading = true;
                fetch(newUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(async res => {
                        const contentType = res.headers.get('Content-Type') ?? '';
                        if (contentType.includes('application/json')) {
                            const json = await res.json();
                            if (!json.success) {
                                AppBase.showFlash('danger', json.errors?.[0]?.message ?? 'Error');
                            }
                            isLoading = false;
                            return;
                        }
                        return res.text();
                    })
                    .then(html => { if (html) openModal(html); })
                    .catch(() => { isLoading = false; });
            });
        }

        document.querySelectorAll<HTMLElement>('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                if (isLoading || currentModal) {
                    return;
                }
                const url = btn.dataset.url;
                if (url) {
                    isLoading = true;
                    fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(res => res.text())
                        .then(openModal)
                        .catch(() => { isLoading = false; });
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

            const deleteUrl = button.dataset.deleteUrl;
            const itemName  = button.dataset.itemName;
            const token     = button.dataset.token;

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

        const form = deleteModal.querySelector<HTMLFormElement>('#delete-form');
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();

                const res = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const json = await res.json();
                const modalInstance = Modal.getInstance(deleteModal);

                deleteModal.addEventListener('hidden.bs.modal', () => {
                    if (json.success) {
                        AppBase.showFlashAfterReload('success', json.message ?? 'Deleted successfully.');
                    } else {
                        AppBase.showFlashAfterReload('danger', json.error ?? 'An error occurred.');
                    }
                    window.location.reload();
                }, { once: true });

                modalInstance?.hide();
            });
        }
    }

    static showFlash(type: FlashType, message: string): void {
        const container = document.querySelector('.flash-container');
        if (!container) return;

        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show shadow`;
        alert.setAttribute('role', 'alert');
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        container.prepend(alert);

        setTimeout(() => {
            Alert.getOrCreateInstance(alert).close();
        }, FLASH_TIME_MS);
    }

    static showFlashAfterReload(type: FlashType, message: string): void {
        sessionStorage.setItem('flash', JSON.stringify({ type, message }));
    }

    static initPendingFlash(): void {
        const raw = sessionStorage.getItem('flash');
        if (!raw) return;
        sessionStorage.removeItem('flash');
        const { type, message } = JSON.parse(raw);
        AppBase.showFlash(type as FlashType, message);
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

    static initAutoHideAlerts({ delay = FLASH_TIME_MS }: AutoHideAlertsConfig = {}): void {
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
