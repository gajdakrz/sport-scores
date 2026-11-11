import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'teamModalContainer',
        modalId: 'teamModal',
        newBtnId: 'newTeamBtn',
        newUrl: '/teams/new',
        onLoaded: () => this.initTeamFormListeners()
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
        AppBase.initDeleteModal();
    }

    /**
     * A national team plays only international competitions, so its competition country is cleared and locked.
     * Only a UX helper – the server validates the same rule (TeamCompetitionCountryAllowed).
     * The modal markup is rendered anew on every open, so listeners don't need to be removed.
     */
    private readonly initTeamFormListeners = (): void => {
        const teamTypeSelect = document.getElementById('team_teamType') as HTMLSelectElement | null;
        const competitionCountrySelect = document.getElementById('team_competitionCountry') as HTMLSelectElement | null;

        if (!teamTypeSelect || !competitionCountrySelect) {
            return;
        }

        const syncCompetitionCountry = (): void => {
            const isNational = teamTypeSelect.value !== ''
                && teamTypeSelect.value === teamTypeSelect.dataset.nationalValue;

            if (isNational) {
                competitionCountrySelect.value = '';
            }

            competitionCountrySelect.disabled = isNational;
        };

        teamTypeSelect.addEventListener('change', syncCompetitionCountry);
        syncCompetitionCountry();
    };
}
