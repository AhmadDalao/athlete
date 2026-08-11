import { Alert, Collapse, Offcanvas } from 'bootstrap';
import '../css/app.css';
import { initializeAlerts } from './modules/alerts';
import { initializeMobileNavigation } from './modules/mobile-navigation';
import { observeInterfaceChanges } from './modules/observer';
import { initializeTheme } from './modules/theme';

const initializeInterface = (root = document) => {
    initializeAlerts(root, Alert);
    initializeMobileNavigation(root, Offcanvas);
    initializeTheme(root);

    root.querySelectorAll('[data-bs-toggle="collapse"]').forEach((trigger) => {
        const selector = trigger.getAttribute('data-bs-target');
        const target = selector ? document.querySelector(selector) : null;

        if (target) {
            Collapse.getOrCreateInstance(target, { toggle: false });
        }
    });
};

document.addEventListener('DOMContentLoaded', () => {
    initializeInterface();
    observeInterfaceChanges(initializeInterface);
});
