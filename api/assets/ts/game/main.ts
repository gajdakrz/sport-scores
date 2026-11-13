import { ModalInitializer } from './modalInitializer';
import { initResultsExpander } from './resultsExpander';
import { initCurrentSeasonToggle } from './currentSeasonToggle';

document.addEventListener('DOMContentLoaded', () => {
    new ModalInitializer().init();
    initResultsExpander();
    initCurrentSeasonToggle();
});


