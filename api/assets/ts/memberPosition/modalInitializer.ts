import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'memberPositionModalContainer',
        modalId: 'memberPositionModal',
        newBtnId: 'newMemberPositionBtn',
        newUrl: '/member-positions/new'
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}
