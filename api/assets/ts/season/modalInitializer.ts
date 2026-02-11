import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'seasonModalContainer',
        modalId: 'seasonModal',
        newBtnId: 'newSeasonBtn',
        newUrl: '/seasons/new'
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}
