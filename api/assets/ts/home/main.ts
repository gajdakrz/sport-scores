document.addEventListener('DOMContentLoaded', () => {
    const sportSelect = document.getElementById('sport-select') as HTMLSelectElement | null;

    if (!sportSelect) {
        return;
    }

    sportSelect.addEventListener('change', async (event: Event) => {
        const target = event.target as HTMLSelectElement;
        const sportId = target.value;

        if (!sportId) {
            return;
        }

        await fetch(`/sports/set/${sportId}`, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        globalThis.location.href = '/games';
    });
});
