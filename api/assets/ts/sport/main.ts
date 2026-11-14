import { AppBase } from '../common/base';
import { ModalInitializer } from './modalInitializer';

document.addEventListener('DOMContentLoaded', () => {
    new ModalInitializer().init();

    document.addEventListener('click', async (e) => {
        const target = e.target as HTMLElement;

        if (!target.classList.contains('select-btn')) return;

        const sportId = target.dataset.id;
        if (!sportId) return;

        try {
            await AppBase.selectSportAndOpenGames(sportId);
        } catch (err) {
            console.error('Failed to switch sport', err);
        }
    });
});
