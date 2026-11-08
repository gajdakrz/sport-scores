import {AppBase, ModalFeatureConfig} from '../common/base';
import {filterTeamOptions, selectedOptionCriteria} from '../common/teamFilter';

export class ModalInitializer {
    private static readonly MIN_RESULTS = 2;

    private competitionChangeHandler: ((this: HTMLSelectElement) => Promise<void>) | null = null;

    private readonly config: ModalFeatureConfig = {
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/games/new',
        onLoaded: () => this.initGameFormListeners()
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
        AppBase.initDeleteModal();
    }

    private readonly initGameFormListeners = (): void => {
        const competitionSelect = document.getElementById('game_competition') as HTMLSelectElement | null;
        const eventSelect = document.getElementById('game_event') as HTMLSelectElement | null;
        const modal = document.getElementById('gameModal');

        if (!competitionSelect || !eventSelect || !modal) {
            console.error('One or more elements not found!');
            return;
        }

        if (this.competitionChangeHandler) {
            competitionSelect.removeEventListener('change', this.competitionChangeHandler);
            this.competitionChangeHandler = null;
        }

        this.competitionChangeHandler = async function (this: HTMLSelectElement) {
            ModalInitializer.removeAllResults();
            ModalInitializer.updateButtons(competitionSelect);

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
                    const option = document.createElement('option');
                    option.value = e.id;
                    option.textContent = e.name;
                    eventSelect.appendChild(option);
                });
            } catch (err) {
                console.error('Error loading events:', err);
                alert('Failed to load events');
            }
        };

        competitionSelect.addEventListener('change', this.competitionChangeHandler);

        const initialCompetitionId = modal.dataset.initialCompetition;
        const initialEventId = modal.dataset.initialEvent;

        if (initialCompetitionId && initialEventId) {
            void this.loadInitialData(initialCompetitionId, initialEventId, competitionSelect, eventSelect);
        }

        this.initGameResultsCollection(competitionSelect);
        ModalInitializer.filterResultTeams(competitionSelect);
        ModalInitializer.updateButtons(competitionSelect);
    };

    /**
     * Results are chosen from teams of the selected competition, so they can be added only once a competition is set.
     * A game needs at least MIN_RESULTS results, so it can be saved only once there are enough
     * (the server enforces the same rule on Game::getGameResults()).
     */
    private static updateButtons(competitionSelect: HTMLSelectElement): void {
        const addButton = document.getElementById('addGameResult') as HTMLButtonElement | null;
        const saveButton = document.querySelector<HTMLButtonElement>('#gameModal button[type="submit"]');
        const resultCount = document.querySelectorAll('.game-results-list .game-result-item').length;

        if (addButton) {
            addButton.disabled = competitionSelect.value === '';
        }

        if (saveButton) {
            saveButton.disabled = resultCount < ModalInitializer.MIN_RESULTS;
        }
    }

    /**
     * A competition change invalidates every chosen team, so all results are removed,
     * the same way as with their Remove button (nothing is deleted until the form is saved).
     */
    private static removeAllResults(): void {
        document
            .querySelectorAll<HTMLElement>('.game-results-list .game-result-item')
            .forEach(item => item.remove());
    }

    private static filterResultTeams(competitionSelect: HTMLSelectElement): void {
        const criteria = selectedOptionCriteria(competitionSelect);

        document
            .querySelectorAll<HTMLSelectElement>('.game-results-list select[id$="_team"]')
            .forEach(teamSelect => filterTeamOptions(teamSelect, criteria));
    }

    private async loadInitialData(
        initialCompetitionId: string,
        initialEventId: string,
        competitionSelect: HTMLSelectElement,
        eventSelect: HTMLSelectElement
    ): Promise<void> {
        try {
            competitionSelect.value = initialCompetitionId;
            ModalInitializer.filterResultTeams(competitionSelect);
            ModalInitializer.updateButtons(competitionSelect);

            const urlTemplate = competitionSelect.dataset.url;
            if (!urlTemplate) return;

            const eventUrl = urlTemplate.replace('COMPETITION_ID', initialCompetitionId);
            const eventResponse = await fetch(eventUrl);

            if (!eventResponse.ok) {
                console.error('Failed to fetch events:', eventResponse.statusText);
                return;
            }

            const events: Array<{ id: string; name: string }> = await eventResponse.json();

            eventSelect.innerHTML = '<option value="">Select event</option>';

            events.forEach(e => {
                const option = document.createElement('option');
                option.value = e.id;
                option.textContent = e.name;
                option.selected = Number(e.id) === Number(initialEventId);
                eventSelect.appendChild(option);
            });
        } catch (err) {
            console.error('Error loading initial data:', err);
        }
    }

    private initGameResultsCollection(competitionSelect: HTMLSelectElement): void {
        const container = document.querySelector('.game-results-list') as HTMLElement | null;
        const addButton = document.getElementById('addGameResult');

        if (!container || !addButton) return;

        let index = Number.parseInt(container.dataset.index || '0', 10);

        addButton.addEventListener('click', () => {
            const prototype = container.dataset.prototype;
            if (!prototype || competitionSelect.value === '') return;

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

                row.appendChild(createCol('col-md-6', fields[0]));
                row.appendChild(createCol('col-md-2', fields[1]));
                row.appendChild(createCol('col-md-2', fields[2]));

                const buttonCol = document.createElement('div');
                buttonCol.classList.add('col-md-2', 'd-flex', 'align-items-center', 'pb-3');
                buttonCol.innerHTML = `
                    <button type="button" class="btn btn-danger btn-sm remove-result">
                        <i class="bi bi-plus-circle"></i>Remove
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
            ModalInitializer.filterResultTeams(competitionSelect);
            ModalInitializer.updateButtons(competitionSelect);
            index++;
            container.dataset.index = index.toString();
        });

        container.addEventListener('click', (e: Event) => {
            const target = e.target as HTMLElement;

            const item = target.closest('.remove-result')?.closest('.game-result-item');

            if (item) {
                item.remove();
                ModalInitializer.updateButtons(competitionSelect);
            }
        });
    }
}
