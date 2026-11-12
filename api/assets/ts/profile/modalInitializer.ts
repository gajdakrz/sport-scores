import { AppBase, ModalFeatureConfig } from '../common/base';

/**
 * "Edit profile" from the navbar user menu – loaded on every page through the app entry.
 */
export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'profileModalContainer',
        modalId: 'profileModal',
        newBtnId: 'editProfileBtn',
        newUrl: '/profile/edit',
        // don't hijack the .edit-btn buttons of the page's own module
        editBtnSelector: null,
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
    }
}
