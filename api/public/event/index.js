document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'eventModalContainer',
        modalId: 'eventModal',
        newBtnId: 'newEventBtn',
        newUrl: '/event/new'
    });

    AppBase.initDeleteModal();
});
