document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'gameModalContainer',
        modalId: 'gameModal',
        newBtnId: 'newGameBtn',
        newUrl: '/game/new'
    });

    AppBase.initDeleteModal();
});
