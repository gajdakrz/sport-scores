document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'sportModalContainer',
        modalId: 'sportModal',
        newBtnId: 'newSportBtn',
        newUrl: '/sports/new'
    });

    AppBase.initDeleteModal();
});
