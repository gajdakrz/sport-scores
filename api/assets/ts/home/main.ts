import { AppBase } from '../common/base';

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

        await AppBase.selectSportAndOpenGames(sportId);
    });
});
