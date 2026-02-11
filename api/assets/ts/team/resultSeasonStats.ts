document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector<HTMLTableSectionElement>('#gameResultTable tbody');

    if (!tableBody) {
        console.warn('Nie znaleziono tbody tabeli #gameResultTable');

        return;
    }

    tableBody.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        if (!target) {
            return;
        }

        const btn = target.closest<HTMLButtonElement>('.season-details');
        if (!btn) {
            return;
        }

        const teamId = btn.dataset.teamId;
        const seasonId = btn.dataset.seasonId;
        const competitionId = btn.dataset.competitionId;

        if (!teamId || !seasonId || !competitionId) {
            console.error('Brak danych w dataset przycisku season-details', btn.dataset);
            return;
        }

        fetch(`/teams/${teamId}/seasons/${seasonId}/competitions/${competitionId}/details`)
            .then((res) => res.text())
            .then((html: string) => {
                const container = document.getElementById('teamSeasonDetailsModalContainer');
                if (!container) {
                    console.error('Nie znaleziono kontenera #teamSeasonDetailsModalContainer');
                    return;
                }

                container.innerHTML = html;

                const modalEl = container.querySelector<HTMLElement>('.modal');
                if (!modalEl) {
                    console.error('Nie znaleziono elementu .modal w kontenerze');
                    return;
                }

                const modal = new bootstrap.Modal(modalEl);
                modal.show();
            })
            .catch((err) => console.error('Błąd fetch:', err));
    });
});
