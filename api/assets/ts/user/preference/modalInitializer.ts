import { AppBase, ModalFeatureConfig } from '../../common/base';
import { initOptionsBySport } from './optionsBySport';

const CONTAINER_ID = 'preferenceModalContainer';

/**
 * "Preferences" from the navbar user menu – loaded on every page through the app entry.
 */
export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: CONTAINER_ID,
        modalId: 'preferenceModal',
        newBtnId: 'editPreferencesBtn',
        newUrl: '/preferences/edit',
        // don't hijack the .edit-btn buttons of the page's own module
        editBtnSelector: null,
        onLoaded: () => {
            const container = document.getElementById(CONTAINER_ID);
            if (!container) return;

            initOptionsBySport(container, 'preference_favouriteSport', [
                'preference_favouriteCompetition',
                'preference_favouriteTeam',
            ]);
        },
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
    }
}
