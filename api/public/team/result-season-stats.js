document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('#gameResultTable tbody');

    if (!tableBody) {
        return;
    }

    tableBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.season-details');
        if (!btn) {
            return;
        }

        const teamId = btn.dataset.teamId;
        const seasonId = btn.dataset.seasonId;
        const competitionId = btn.dataset.competitionId;

        fetch(`/teams/${teamId}/seasons/${seasonId}/competitions/${competitionId}/details`)
            .then(res => res.text())
            .then(html => {

                console.log('Response HTML:', html); // DODAJ TO

                const container = document.getElementById('teamSeasonDetailsModalContainer');
                container.innerHTML = html;

                // Sprawdź co jest w DOM po wstawieniu
                console.log('Body content:', container.querySelector('#season-details-body')?.innerHTML);

                const modalEl = container.querySelector('.modal');
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            });
    });
});
