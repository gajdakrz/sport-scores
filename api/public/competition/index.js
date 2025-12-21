document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'competitionModalContainer',
        modalId: 'competitionModal',
        newBtnId: 'newCompetitionBtn',
        newUrl: '/competitions/new'
    });
});
