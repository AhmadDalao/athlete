import { Alert, Offcanvas } from 'bootstrap';
import '../css/app.css';
import { initializeAlerts } from './modules/alerts';
import { initializeMobileNavigation } from './modules/mobile-navigation';
import { observeInterfaceChanges } from './modules/observer';

const initializeInterface = (root = document) => {
    initializeAlerts(root, Alert);
    initializeMobileNavigation(root, Offcanvas);
};

document.addEventListener('DOMContentLoaded', () => {
    initializeInterface();
    observeInterfaceChanges(initializeInterface);
});
