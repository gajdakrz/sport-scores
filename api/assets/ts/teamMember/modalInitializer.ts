import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private teamChangeHandler: ((this: HTMLSelectElement) => Promise<void>) | null = null;
    private isCurrentChangeHandler: ((event: Event) => Promise<void>) | null = null;

    private readonly config: ModalFeatureConfig = {
        containerId: 'teamMemberModalContainer',
        modalId: 'teamMemberModal',
        newBtnId: 'newTeamMemberBtn',
        newUrl: '/team-members/new',
        onLoaded: () => this.initTeamMemberFormListeners()
    };

    public init(): void {
        AppBase.initPendingFlash();
        AppBase.initModalFeature(this.config);
        AppBase.initDeleteModal();
    }

    private initTeamMemberFormListeners = (): void => {
        const teamSelect = document.getElementById('team_member_team') as HTMLSelectElement | null;
        const personSelect = document.getElementById('team_member_person') as HTMLSelectElement | null;
        const modal = document.getElementById('teamMemberModal') as HTMLElement | null;
        const isCurrentCheckbox = document.getElementById('team_member_isCurrentMember') as HTMLInputElement | null;

        if (!teamSelect || !personSelect || !modal || !isCurrentCheckbox) {
            console.error('One or more elements not found!');
            return;
        }

        const teamMemberId = modal.dataset.teamMemberId ?? '';
        let teamFilter =  teamMemberId === '' ? 'excluded' : 'all';

        if (this.teamChangeHandler) {
            teamSelect.removeEventListener('change', this.teamChangeHandler);
            this.teamChangeHandler = null;
        }

        this.teamChangeHandler = async function (this: HTMLSelectElement) {
            if (!this.value) {
                personSelect.innerHTML = '<option value="">Select person</option>';
                return;
            }

            const urlTemplate = teamSelect.dataset.url;
            if (!urlTemplate) return;

            // teamFilter pochodzi z closure - zawsze aktualny
            const url = urlTemplate
                .replace('TEAM_FILTER', teamFilter)
                .replace('TEAM_ID', this.value);

            try {
                const personResponse = await fetch(url);
                const persons: Array<{
                    id: string;
                    firstName: string;
                    lastName: string;
                    currentTeamName: string;
                }> = await personResponse.json();

                personSelect.innerHTML = '<option value="">Select person</option>';
                persons.forEach(e => {
                    const option = document.createElement('option');
                    option.value = e.id;
                    option.textContent = `${e.firstName} ${e.lastName} (${e.currentTeamName})`;
                    personSelect.appendChild(option);
                });
            } catch (err) {
                console.error('Error loading persons:', err);
                alert('Failed to load persons');
            }
        };

        teamSelect.addEventListener('change', this.teamChangeHandler);

        if (this.isCurrentChangeHandler) {
            isCurrentCheckbox.removeEventListener('change', this.isCurrentChangeHandler);
            this.isCurrentChangeHandler = null;
        }

        this.isCurrentChangeHandler = async (event: Event) => {
            const checkbox = event.target as HTMLInputElement;
            teamFilter = (checkbox.checked && teamMemberId === '') ? 'excluded' : 'all';

            if (this.teamChangeHandler && teamSelect.value) {
                await this.teamChangeHandler.call(teamSelect);
            }
        };

        isCurrentCheckbox.addEventListener('change', this.isCurrentChangeHandler);

        const initialTeamId = modal.dataset.initialTeam;
        const initialPersonId = modal.dataset.initialPerson;

        if (initialTeamId && initialPersonId) {
            void this.loadInitialData(initialTeamId, initialPersonId, teamSelect, personSelect);
        }
    };

    private async loadInitialData(
        initialTeamId: string,
        initialPersonId: string,
        teamSelect: HTMLSelectElement,
        personSelect: HTMLSelectElement
    ): Promise<void> {
        try {
            teamSelect.value = initialTeamId;
            const urlTemplate = teamSelect.dataset.url;
            let teamFilter = 'all';
            if (!urlTemplate) return;

            const personUrl = urlTemplate
                .replace('TEAM_FILTER', teamFilter)
                .replace('TEAM_ID', initialTeamId);
            const personResponse = await fetch(personUrl);

            if (!personResponse.ok) {
                console.error('Failed to fetch persons:', personResponse.statusText);
                return;
            }

            const persons: Array<{
                id: string;
                firstName: string;
                lastName: string;
                currentTeamName: string;
            }> = await personResponse.json();

            personSelect.innerHTML = '<option value="">Select person</option>';
            persons.forEach(e => {
                const option = document.createElement('option');
                option.value = e.id;
                option.textContent = `${e.firstName} ${e.lastName} (${e.currentTeamName})`;
                option.selected = Number(e.id) === Number(initialPersonId);
                personSelect.appendChild(option);
            });
        } catch (err) {
            console.error('Error loading initial data:', err);
        }
    }
}
