document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'teamModalContainer',
        modalId: 'teamModal',
        newBtnId: 'newTeamBtn',
        newUrl: '/teams/new'
    });
    AppBase.initDeleteModal();
    AppBase.initAutoHideAlerts();
});

document.querySelectorAll('.expand-results').forEach(btn => {
    btn.addEventListener('click', async () => {
        const tr = btn.closest('tr');
        const teamId = btn.dataset.id;
        const existing = document.querySelector('.game-results-row');

        if (existing) {
            existing.remove();
            if (existing.previousElementSibling === tr) return;
        }

        try {
            const res = await fetch(`/teams/${teamId}/results`);
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
