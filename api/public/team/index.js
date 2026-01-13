document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'teamModalContainer',
        modalId: 'teamModal',
        newBtnId: 'newTeamBtn',
        newUrl: '/teams/new'
    });
});
