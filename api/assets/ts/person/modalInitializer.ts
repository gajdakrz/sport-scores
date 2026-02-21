import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'personModalContainer',
        modalId: 'personModal',
        newBtnId: 'newPersonBtn',
        newUrl: '/persons/new'
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}
