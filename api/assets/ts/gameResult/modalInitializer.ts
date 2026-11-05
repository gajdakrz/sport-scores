import { AppBase, ModalFeatureConfig } from '../common/base';
import { filterTeamOptionsByGender, selectedOptionGender } from '../common/genderFilter';

export class ModalInitializer {
    private gameChangeHandler: (() => void) | null = null;

    private readonly config: ModalFeatureConfig = {
        containerId: 'gameResultModalContainer',
        modalId: 'gameResultModal',
        newBtnId: 'newGameResultBtn',
        newUrl: '/game-results/new',
        onLoaded: () => this.initGameResultFormListeners()
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
        AppBase.initDeleteModal();
    }

    private readonly initGameResultFormListeners = (): void => {
        const gameSelect = document.getElementById('game_result_game') as HTMLSelectElement | null;
        const teamSelect = document.getElementById('game_result_team') as HTMLSelectElement | null;

        if (!gameSelect || !teamSelect) {
            return;
        }

        if (this.gameChangeHandler) {
            gameSelect.removeEventListener('change', this.gameChangeHandler);
        }

        this.gameChangeHandler = (): void => {
            filterTeamOptionsByGender(teamSelect, selectedOptionGender(gameSelect));
        };

        gameSelect.addEventListener('change', this.gameChangeHandler);
        this.gameChangeHandler();
    };
}
