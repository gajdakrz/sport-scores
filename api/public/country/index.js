document.addEventListener('DOMContentLoaded', () => {
    AppBase.initModalFeature({
        containerId: 'countryModalContainer',
        modalId: 'countryModal',
        newBtnId: 'newCountryBtn',
        newUrl: '/country/new'
    });

    AppBase.initDeleteModal();
});
