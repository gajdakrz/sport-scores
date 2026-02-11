import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'countryModalContainer',
        modalId: 'countryModal',
        newBtnId: 'newCountryBtn',
        newUrl: '/countries/new'
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }
}
