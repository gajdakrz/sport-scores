import { AppBase } from './common/base';
import { ModalInitializer as ProfileModalInitializer } from './profile/modalInitializer';

document.addEventListener('DOMContentLoaded', () => {
    AppBase.initSportSwitcher();
    AppBase.initAutoHideAlerts();
    AppBase.initDeleteModal();
    AppBase.initUniversalModalFocusHandler();
    new ProfileModalInitializer().init();
});
