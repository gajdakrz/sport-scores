document.addEventListener('DOMContentLoaded', () => {
    const sportSelect = document.getElementById('sport-select');

    if (!sportSelect) {
        return;
    }

    sportSelect.addEventListener('change', async (event) => {
        const sportId = event.target.value;
        if (!sportId) {
            return;
        }

        await fetch(`sports/set/${sportId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        window.location.href = '/games';
    });
});
