document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/games/new',
        onLoaded: initGameFormListeners
    });

    AppBase.initDeleteModal();

    document.querySelectorAll('.expand-results').forEach(btn => {
        btn.addEventListener('click', async () => {
            const tr = btn.closest('tr');
            const gameId = btn.dataset.id;
            const existing = document.querySelector('.game-results-row');

            if (existing) {
                existing.remove();
                if (existing.previousElementSibling === tr) return;
            }

            try {
                const res = await fetch(`/games/${gameId}/results`);
                if (!res.ok) {
                    throw new Error('Failed to fetch results');
                }
                const html = await res.text();
                const row = document.createElement('tr');
                row.classList.add('game-results-row');
                row.innerHTML = `<td colspan="8">${html}</td>`;
                tr.insertAdjacentElement('afterend', row);
            } catch (err) {
                console.error(err);
                alert('Error loading results');
            }
        });
    });
});

function initGameFormListeners() {
    const sportSelect = document.getElementById('game_sport');
    const competitionSelect = document.getElementById('game_competition');
    const eventSelect = document.getElementById('game_event');
    const modal = document.getElementById('gameModal');
    const sportSelectFilter = document.getElementById('sportSelectFilter');

    if (!sportSelect || !competitionSelect || !eventSelect || !modal) {
        console.error('One or more elements not found!');
        return;
    }

    sportSelect.value = sportSelectFilter.value;

    const initialSportId = modal.dataset.initialSport;
    const initialCompetitionId = modal.dataset.initialCompetition;
    const initialEventId = modal.dataset.initialEvent;

    if (initialSportId && initialCompetitionId && initialEventId) {
        loadInitialData(initialSportId, initialCompetitionId, initialEventId);
    }

    // Sport change handler
    sportSelect.addEventListener('change', async function () {
        if (!this.value) {
            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            eventSelect.innerHTML = '<option value="">Select event</option>';
            return;
        }

        const url = sportSelect.dataset.url.replace('SPORT_ID', this.value);

        try {
            const res = await fetch(url);
            const competitions = await res.json();

            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            eventSelect.innerHTML = '<option value="">Select event</option>';

            competitions.forEach(c => {
                competitionSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        } catch (err) {
            console.error('Error loading competitions:', err);
            alert('Failed to load competitions');
        }
    });

    // Competition change handler
    competitionSelect.addEventListener('change', async function () {
        if (!this.value) {
            eventSelect.innerHTML = '<option value="">Select event</option>';
            return;
        }

        const url = competitionSelect.dataset.url.replace('COMP_ID', this.value);

        try {
            const res = await fetch(url);
            const events = await res.json();

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

    async function loadInitialData(sportId, competitionId, eventId) {
        try {
            sportSelect.value = sportId;

            const compUrl = sportSelect.dataset.url.replace('SPORT_ID', sportId);
            const compRes = await fetch(compUrl);
            const competitions = await compRes.json();

            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            competitions.forEach(c => {
                const selected = c.id == competitionId ? 'selected' : '';
                competitionSelect.innerHTML += `<option value="${c.id}" ${selected}>${c.name}</option>`;
            });

            const eventUrl = competitionSelect.dataset.url.replace('COMP_ID', competitionId);
            const eventRes = await fetch(eventUrl);
            const events = await eventRes.json();

            eventSelect.innerHTML = '<option value="">Select event</option>';
            events.forEach(e => {
                const selected = e.id == eventId ? 'selected' : '';
                eventSelect.innerHTML += `<option value="${e.id}" ${selected}>${e.name}</option>`;
            });
        } catch (err) {
            console.error('Error loading initial data:', err);
        }
    }
}

function initGameResultsCollection() {
    const container = document.querySelector('.game-results-list');
    const addButton = document.getElementById('addGameResult');

    if (!container || !addButton) return;

    let index = parseInt(container.dataset.index) || 0;

    // Add new game result
    addButton.addEventListener('click', () => {
        const prototype = container.dataset.prototype;
        const newForm = prototype.replace(/__name__/g, index);

        const div = document.createElement('div');
        div.classList.add('game-result-item', 'card', 'mb-2');
        div.innerHTML = `
            <div class="card-body">
                <div class="row g-2">
                    ${newForm}
                </div>
            </div>
        `;

        const fields = div.querySelectorAll('.mb-3');
        if (fields.length >= 3) {
            const row = document.createElement('div');
            row.classList.add('row', 'g-2');

            const teamCol = document.createElement('div');
            teamCol.classList.add('col-md-5');
            teamCol.appendChild(fields[0]);

            const matchCol = document.createElement('div');
            matchCol.classList.add('col-md-3');
            matchCol.appendChild(fields[1]);

            const rankingCol = document.createElement('div');
            rankingCol.classList.add('col-md-3');
            rankingCol.appendChild(fields[2]);

            const buttonCol = document.createElement('div');
            buttonCol.classList.add('col-md-1', 'd-flex', 'align-items-center', 'pb-3');
            buttonCol.innerHTML = `
                <button type="button" class="btn btn-danger btn-sm remove-result w-100" title="Remove">
                    <i class="bi bi-trash"></i>
                </button>
            `;

            row.appendChild(teamCol);
            row.appendChild(matchCol);
            row.appendChild(rankingCol);
            row.appendChild(buttonCol);

            div.querySelector('.card-body').innerHTML = '';
            div.querySelector('.card-body').appendChild(row);
        }

        container.appendChild(div);
        index++;
        container.dataset.index = index;
    });

    // Remove game result - soft delete
    container.addEventListener('click', (e) => {
        const removeBtn = e.target.closest('.remove-result');
        if (removeBtn) {
            const item = removeBtn.closest('.game-result-item');
            const hasId = item.querySelector('input[id$="_id"]')?.value;

            if (!hasId) {
                item.remove();
            } else {
                item.style.opacity = '0.5';
                item.style.textDecoration = 'line-through';
                const form = item.querySelector('.card-body');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = item.querySelector('select, input').name.replace(/\[.*?\]$/, '[isActive]');
                hiddenInput.value = '0';
                form.appendChild(hiddenInput);

                item.querySelectorAll('select, input').forEach(field => {
                    if (field !== hiddenInput) {
                        field.disabled = true;
                    }
                });

                removeBtn.classList.remove('btn-danger');
                removeBtn.classList.add('btn-success');
                removeBtn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
                removeBtn.classList.remove('remove-result');
                removeBtn.classList.add('restore-result');
            }
        }

        // Restore result
        const restoreBtn = e.target.closest('.restore-result');
        if (restoreBtn) {
            const item = restoreBtn.closest('.game-result-item');

            item.style.opacity = '1';
            item.style.textDecoration = 'none';

            const hiddenInput = item.querySelector('input[name$="[isActive]"]');
            if (hiddenInput) {
                hiddenInput.remove();
            }

            item.querySelectorAll('select, input').forEach(field => {
                field.disabled = false;
            });

            restoreBtn.classList.remove('btn-success');
            restoreBtn.classList.add('btn-danger');
            restoreBtn.innerHTML = '<i class="bi bi-trash"></i>';
            restoreBtn.classList.remove('restore-result');
            restoreBtn.classList.add('remove-result');
        }
    });
}
