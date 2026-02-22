import { Modal } from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector<HTMLTableSectionElement>('#gameResultTable tbody');

    if (!tableBody) {
        console.warn('Nie znaleziono tbody tabeli #gameResultTable');
        return;
    }

    let modalInstance: Modal | null = null;

    function renderModal(html: string): void {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const container = document.getElementById('teamSeasonDetailsModalContainer');
        if (!container) {
            console.error('Nie znaleziono kontenera #teamSeasonDetailsModalContainer');
            return;
        }

        if (!modalInstance) {
            // Pierwsze otwarcie — wstawiamy cały HTML i tworzymy instancję modala
            container.innerHTML = html;
            const modalEl = container.querySelector<HTMLElement>('.modal');
            if (!modalEl) {
                console.error('Nie znaleziono elementu .modal w kontenerze');
                return;
            }
            modalInstance = new Modal(modalEl);
            modalInstance.show();
        } else {
            // Paginacja — tylko aktualizujemy body
            const modalBody = container.querySelector<HTMLElement>('.modal-body');
            const fetchedModalBody = doc.querySelector('.modal-body');
            if (!modalBody || !fetchedModalBody) {
                console.error('Nie znaleziono .modal-body');
                return;
            }
            modalBody.innerHTML = fetchedModalBody.innerHTML;
        }

        bindPaginationLinks(container);
    }

    function loadContent(url: string): void {
        fetch(url)
            .then((res) => res.text())
            .then((html: string) => renderModal(html))
            .catch((err) => console.error('Błąd fetch:', err));
    }

    function bindPaginationLinks(container: HTMLElement): void {
        container.querySelectorAll<HTMLAnchorElement>('.pagination a').forEach((link) => {
            link.addEventListener('click', (e: MouseEvent) => {
                e.preventDefault();
                const href = link.getAttribute('href');
                if (href) {
                    loadContent(href);
                }
            });
        });
    }

    tableBody.addEventListener('click', (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        const btn = target?.closest<HTMLButtonElement>('.season-details');
        if (!btn) return;

        const { teamId, seasonId, competitionId } = btn.dataset;
        if (!teamId || !seasonId || !competitionId) {
            console.error('Brak danych w dataset przycisku season-details', btn.dataset);
            return;
        }

        modalInstance = null; // reset przy każdym nowym otwarciu
        loadContent(`/teams/${teamId}/seasons/${seasonId}/competitions/${competitionId}/details`);
    });
});
