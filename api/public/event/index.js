document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'eventModalContainer',
        modalId: 'eventModal',
        newBtnId: 'newEventBtn',
        newUrl: '/events/new',
        onLoaded: initEventFormListeners
    });
});

function initEventFormListeners() {
    const competitionSelect = document.getElementById('event_competition');
    const orderIndex = document.getElementById('event_orderIndex');

    if (!competitionSelect || !orderIndex) {
        console.error('One or more elements not found!');
        return;
    }

    competitionSelect.addEventListener('change', async function () {
        if (!this.value) {
            return;
        }
        const selectedOption = this.options[this.selectedIndex];
        orderIndex.disabled = selectedOption.dataset.isBracket === 'false';

        if (orderIndex.disabled) {
            orderIndex.value = '';
        }
    });
}
