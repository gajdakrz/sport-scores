document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'eventModalContainer',
        modalId: 'eventModal',
        newBtnId: 'newEventBtn',
        newUrl: '/events/new',
        onLoaded: initEventFormListeners
    });
    AppBase.initDeleteModal();
    AppBase.initAutoHideAlerts();
});


function initEventFormListeners() {
    const sportSelect = document.getElementById('event_sport');
    const competitionSelect = document.getElementById('event_competition');
    const modal = document.getElementById('eventModal');

    if (!sportSelect || !competitionSelect || !modal) {
        console.error('One or more elements not found!');
        return;
    }

    const initialSportId = modal.dataset.initialSport;
    const initialCompetitionId = modal.dataset.initialCompetition;

    if (initialSportId && initialCompetitionId) {
        loadInitialData(initialSportId, initialCompetitionId);
    }

    // Sport change handler
    sportSelect.addEventListener('change', async function () {
        if (!this.value) {
            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            return;
        }

        const url = sportSelect.dataset.url.replace('SPORT_ID', this.value);

        try {
            const res = await fetch(url);
            const competitions = await res.json();

            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            competitions.forEach(c => {
                competitionSelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        } catch (err) {
            console.error('Error loading competitions:', err);
            alert('Failed to load competitions');
        }
    });

    async function loadInitialData(sportId, competitionId) {
        try {
            sportSelect.value = sportId;

            const compUrl = sportSelect.dataset.url.replace('SPORT_ID', sportId);
            const compRes = await fetch(compUrl);
            const competitions = await compRes.json();

            competitionSelect.innerHTML = '<option value="">Select competition</option>';
            competitions.forEach(c => {
                const selected = c.id === competitionId ? 'selected' : '';
                competitionSelect.innerHTML += `<option value="${c.id}" ${selected}>${c.name}</option>`;
            });
        } catch (err) {
            console.error('Error loading initial data:', err);
        }
    }
}
