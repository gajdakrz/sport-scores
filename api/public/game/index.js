document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/games/new'
    });

    AppBase.initDeleteModal();

    document.querySelectorAll('.expand-results').forEach(btn => {
        btn.addEventListener('click', async () => {
            const tr = btn.closest('tr');
            const gameId = btn.dataset.id;
            const existing = document.querySelector('.game-results-row');

            // Collapse existing open section if any
            if (existing) {
                existing.remove();
                // if same game clicked again, stop (collapse only)
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
