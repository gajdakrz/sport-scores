document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/games/new',
        onLoaded: initGameFormListeners // <-- attach listeners after modal is injected
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
                if (!res.ok) throw new Error('Failed to fetch results');
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

    // Get initial values for edit mode
    const initialSportId = modal.dataset.initialSport;
    const initialCompetitionId = modal.dataset.initialCompetition;
    const initialEventId = modal.dataset.initialEvent;

    // Load initial data if editing
    if (initialSportId && initialCompetitionId && initialEventId) {
        loadInitialData(initialSportId, initialCompetitionId, initialEventId);
    }

    // --- load competitions when sport changes ---
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

    // --- load events when competition changes ---
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

    // Load initial data for edit mode
    async function loadInitialData(sportId, competitionId, eventId) {
        try {
            // Set sport
            sportSelect.value = sportId;

            // Load competitions for this sport
            const compUrl = sportSelect.dataset.url.replace('SPORT_ID', sportId);
            const compRes = await fetch(compUrl);
            const competitions = await compRes.json();

            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            competitions.forEach(c => {
                const selected = c.id == competitionId ? 'selected' : '';
                competitionSelect.innerHTML += `<option value="${c.id}" ${selected}>${c.name}</option>`;
            });

            // Load events for this competition
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
