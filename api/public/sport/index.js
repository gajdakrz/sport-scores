document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'sportModalContainer',
        modalId: 'sportModal',
        newBtnId: 'newSportBtn',
        newUrl: '/sport/new'
    });

    AppBase.initDeleteModal();
});
