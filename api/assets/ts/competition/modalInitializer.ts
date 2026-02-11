import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'competitionModalContainer',
        modalId: 'competitionModal',
        newBtnId: 'newCompetitionBtn',
        newUrl: '/competitions/new'
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}
