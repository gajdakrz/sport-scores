export function initResultsExpander(): void {
    document.querySelectorAll<HTMLElement>('.expand-results').forEach(btn => {
        btn.addEventListener('click', async () => {
            const tr = btn.closest('tr') as HTMLTableRowElement | null;
            const gameId = btn.dataset.id;
            const existing = document.querySelector<HTMLElement>('.game-results-row');

            if (!tr || !gameId) {
                return;
            }

            if (existing) {
                const previousSibling = existing.previousElementSibling;
                existing.remove();
                if (previousSibling === tr) {
                    return;
                }
            }

            try {
                const res = await fetch(`/games/${gameId}/results`);
                if (!res.ok) {
                    alert('Error loading results');
                    return;
                }

                const html = await res.text();
                const row = document.createElement('tr');
                row.classList.add('game-results-row');
                row.innerHTML = `<td colspan="8">${html}</td>`;
                tr.after(row);
            } catch (err) {
                console.error(err);
                alert('Error loading results');
            }
        });
    });
}
