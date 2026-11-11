import { Modal } from 'bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector<HTMLTableSectionElement>('#gameResultTable tbody');

    if (!tableBody) {
        console.warn('tbody of the #gameResultTable table not found');
        return;
    }

    let modalInstance: Modal | null = null;

    function renderModal(html: string): void {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        const container = document.getElementById('teamSeasonDetailsModalContainer');
        if (!container) {
            console.error('#teamSeasonDetailsModalContainer container not found');
            return;
        }

        if (modalInstance) {
            // Pagination – update the body only
            const modalBody = container.querySelector<HTMLElement>('.modal-body');
            const fetchedModalBody = doc.querySelector('.modal-body');
            if (!modalBody || !fetchedModalBody) {
                console.error('.modal-body not found');
                return;
            }
            modalBody.innerHTML = fetchedModalBody.innerHTML;
        } else {
            // First open – insert the whole HTML and create the modal instance
            container.innerHTML = html;
            const modalEl = container.querySelector<HTMLElement>('.modal');
            if (!modalEl) {
                console.error('.modal element not found in the container');
                return;
            }
            modalInstance = new Modal(modalEl);
            modalInstance.show();
        }

        bindPaginationLinks(container);
    }

    function loadContent(url: string): void {
        fetch(url)
            .then((res) => res.text())
            .then((html: string) => renderModal(html))
            .catch((err) => console.error('Fetch error:', err));
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
            console.error('Missing data in the season-details button dataset', btn.dataset);
            return;
        }

        modalInstance = null; // reset on every new open
        loadContent(`/teams/${teamId}/seasons/${seasonId}/competitions/${competitionId}/details`);
    });
});
