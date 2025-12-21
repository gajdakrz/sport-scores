document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'countryModalContainer',
        modalId: 'countryModal',
        newBtnId: 'newCountryBtn',
        newUrl: '/countries/new'
    });
});
