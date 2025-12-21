document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'gameResultModalContainer',
        modalId: 'gameResultModal',
        newBtnId: 'newGameResultBtn',
        newUrl: '/game-results/new'
    });
});
