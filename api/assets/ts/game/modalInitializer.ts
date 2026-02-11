import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/games/new',
        onLoaded: initGameFormListeners
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}

function initGameFormListeners(): void {
    const competitionSelect = document.getElementById('game_competition') as HTMLSelectElement | null;
    const eventSelect = document.getElementById('game_event') as HTMLSelectElement | null;
    const modal = document.getElementById('gameModal') as HTMLElement | null;

    if (!competitionSelect || !eventSelect || !modal) {
        console.error('One or more elements not found!');
        return;
    }

    const initialCompetitionId = modal.dataset.initialCompetition;
    const initialEventId = modal.dataset.initialEvent;

    if (initialCompetitionId && initialEventId) {
        void loadInitialData(initialCompetitionId, initialEventId, competitionSelect, eventSelect);
    }

    competitionSelect.addEventListener('change', async function (this: HTMLSelectElement) {
        if (!this.value) {
            eventSelect.innerHTML = '<option value="">Select event</option>';
            return;
        }

        const urlTemplate = competitionSelect.dataset.url;
        if (!urlTemplate) return;

        const url = urlTemplate.replace('COMPETITION_ID', this.value);

        try {
            const res = await fetch(url);
            const events: Array<{ id: string; name: string }> = await res.json();

            eventSelect.innerHTML = '<option value="">Select event</option>';

            events.forEach(e => {
                eventSelect.innerHTML += `<option value="${e.id}">${e.name}</option>`;
            });
        } catch (err) {
            console.error('Error loading events:', err);
            alert('Failed to load events');
        }
    });

    initGameResultsCollection();

    async function loadInitialData(
        competitionId: string,
        eventId: string,
        competitionSelect: HTMLSelectElement,
        eventSelect: HTMLSelectElement
    ): Promise<void> {
        try {
            competitionSelect.value = competitionId;

            const urlTemplate = competitionSelect.dataset.url;
            if (!urlTemplate) return;

            const eventUrl = urlTemplate.replace('COMPETITION_ID', competitionId);
            const eventRes = await fetch(eventUrl);

            if (!eventRes.ok) {
                console.error('Failed to fetch events:', eventRes.statusText);
                return;
            }

            const events: Array<{ id: string; name: string }> = await eventRes.json();

            eventSelect.innerHTML = '<option value="">Select event</option>';

            events.forEach(e => {
                const option = document.createElement('option');
                option.value = e.id;
                option.textContent = e.name;
                option.selected = Number(e.id) === Number(eventId);
                eventSelect.appendChild(option);
            });
        } catch (err) {
            console.error('Error loading initial data:', err);
        }
    }
}

function initGameResultsCollection(): void {
    const container = document.querySelector('.game-results-list') as HTMLElement | null;
    const addButton = document.getElementById('addGameResult') as HTMLElement | null;

    if (!container || !addButton) return;

    let index = parseInt(container.dataset.index || '0', 10);

    addButton.addEventListener('click', () => {
        const prototype = container.dataset.prototype;
        if (!prototype) return;

        const newForm = prototype.replace(/__name__/g, index.toString());

        const div = document.createElement('div');
        div.classList.add('game-result-item', 'card', 'mb-2');

        div.innerHTML = `
            <div class="card-body">
                <div class="row g-2">
                    ${newForm}
                </div>
            </div>
        `;

        const fields = div.querySelectorAll<HTMLElement>('.mb-3');

        if (fields.length >= 3) {
            const row = document.createElement('div');
            row.classList.add('row', 'g-2');

            const createCol = (size: string, field: HTMLElement): HTMLElement => {
                const col = document.createElement('div');
                col.classList.add(size);
                col.appendChild(field);
                return col;
            };

            row.appendChild(createCol('col-md-5', fields[0]));
            row.appendChild(createCol('col-md-3', fields[1]));
            row.appendChild(createCol('col-md-3', fields[2]));

            const buttonCol = document.createElement('div');
            buttonCol.classList.add('col-md-1', 'd-flex', 'align-items-center', 'pb-3');
            buttonCol.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm remove-result w-100" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            `;

            row.appendChild(buttonCol);

            const body = div.querySelector('.card-body');
            if (body) {
                body.innerHTML = '';
                body.appendChild(row);
            }
        }

        container.appendChild(div);
        index++;
        container.dataset.index = index.toString();
    });

    container.addEventListener('click', (e: Event) => {
        const target = e.target as HTMLElement;

        const removeBtn = target.closest('.remove-result') as HTMLElement | null;

        if (removeBtn) {
            const item = removeBtn.closest('.game-result-item') as HTMLElement | null;
            if (!item) return;

            const idInput = item.querySelector<HTMLInputElement>('input[id$="_id"]');
            const hasId = idInput?.value;

            if (!hasId) {
                item.remove();
            } else {
                item.style.opacity = '0.5';
                item.style.textDecoration = 'line-through';

                const form = item.querySelector('.card-body') as HTMLElement | null;
                if (!form) return;

                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';

                const firstField = item.querySelector<HTMLInputElement>('select, input');
                if (!firstField) return;

                hiddenInput.name = firstField.name.replace(/\[[^\]]*]$/, '[isActive]');
                hiddenInput.value = '0';

                form.appendChild(hiddenInput);

                item.querySelectorAll<HTMLInputElement | HTMLSelectElement>('select, input').forEach(field => {
                    if (field !== hiddenInput) {
                        field.disabled = true;
                    }
                });

                removeBtn.classList.remove('btn-danger', 'remove-result');
                removeBtn.classList.add('btn-success', 'restore-result');
                removeBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
            }

            return;
        }

        const restoreBtn = target.closest('.restore-result') as HTMLElement | null;

        if (restoreBtn) {
            const item = restoreBtn.closest('.game-result-item') as HTMLElement | null;
            if (!item) return;

            item.style.opacity = '1';
            item.style.textDecoration = 'none';

            const hiddenInput = item.querySelector<HTMLInputElement>('input[name$="[isActive]"]');
            hiddenInput?.remove();

            item.querySelectorAll<HTMLInputElement | HTMLSelectElement>('select, input').forEach(field => {
                field.disabled = false;
            });

            restoreBtn.classList.remove('btn-success', 'restore-result');
            restoreBtn.classList.add('btn-danger', 'remove-result');
            restoreBtn.innerHTML = '<i class="bi bi-trash"></i>';
        }
    });
}
