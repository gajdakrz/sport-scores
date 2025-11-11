document.addEventListener('DOMContentLoaded', () => {
    const sportSelect = document.getElementById('sport-select');

    if (sportSelect) {
        sportSelect.addEventListener('change', (event) => {
            const sportId = event.target.value;
            if (!sportId) {
                return;
            }
            window.location.href = `/games?sportId=${sportId}`;
        });
    }
});
