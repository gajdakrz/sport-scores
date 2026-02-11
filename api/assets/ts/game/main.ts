import { ModalInitializer } from './modalInitializer';
import { initResultsExpander } from './resultsExpander';

document.addEventListener('DOMContentLoaded', () => {
    new ModalInitializer().init();
    initResultsExpander();
});


