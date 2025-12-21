document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'sportModalContainer',
        modalId: 'sportModal',
        newBtnId: 'newSportBtn',
        newUrl: '/sports/new'
    });
});


document.querySelectorAll('.select-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const sportId = e.currentTarget.dataset.id;

        if (!sportId) {
            return;
        }

        try {
            await fetch(`sports/set/${sportId}`, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            window.location.href = '/games';
        } catch (e) {
            console.error('Failed to switch sport', e);
        }
    });
});
