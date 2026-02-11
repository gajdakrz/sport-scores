import { AppBase } from './common/base';

document.addEventListener('DOMContentLoaded', () => {
    AppBase.initSportSwitcher();
    AppBase.initAutoHideAlerts();
    AppBase.initDeleteModal();
    AppBase.initUniversalModalFocusHandler();
});
