import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'gameResultModalContainer',
        modalId: 'gameResultModal',
        newBtnId: 'newGameResultBtn',
        newUrl: '/game-results/new'
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
        AppBase.initDeleteModal();
    }
}
