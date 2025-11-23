document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'seasonModalContainer',
        modalId: 'seasonModal',
        newBtnId: 'newSeasonBtn',
        newUrl: '/seasons/new'
    });
    AppBase.initDeleteModal();
    AppBase.initAutoHideAlerts();
});
