import { AppBase, ModalFeatureConfig } from '../common/base';

export class ModalInitializer {
    private readonly config: ModalFeatureConfig = {
        containerId: 'eventModalContainer',
        modalId: 'eventModal',
        newBtnId: 'newEventBtn',
        newUrl: '/events/new',
        onLoaded: () => this.initEventFormListeners()
    };

    public init(): void {
        AppBase.initModalFeature(this.config);
    }

    private initEventFormListeners(): void {
        const competitionSelect = document.getElementById('event_competition') as HTMLSelectElement | null;
        const orderIndex = document.getElementById('event_orderIndex') as HTMLInputElement | null;

        if (!competitionSelect || !orderIndex) {
            console.error('One or more elements not found!');
            return;
        }

        competitionSelect.addEventListener('change', function (this: HTMLSelectElement) {
            if (!this.value) {
                return;
            }

            const selectedOption = this.options[this.selectedIndex] as HTMLOptionElement;

            orderIndex.disabled = selectedOption.dataset.isBracket === 'false';

            if (orderIndex.disabled) {
                orderIndex.value = '';
            }
        });
    }
}
