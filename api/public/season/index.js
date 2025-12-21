document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'seasonModalContainer',
        modalId: 'seasonModal',
        newBtnId: 'newSeasonBtn',
        newUrl: '/seasons/new'
    });
});
