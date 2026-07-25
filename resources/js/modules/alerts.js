const initializedAttribute = 'data-tl-alert-ready';

export function initializeAlerts(root, Alert) {
    root.querySelectorAll('[data-tl-auto-dismiss]').forEach((element) => {
        if (element.hasAttribute(initializedAttribute)) {
            return;
        }

        element.setAttribute(initializedAttribute, 'true');

        window.setTimeout(() => {
            if (element.isConnected) {
                Alert.getOrCreateInstance(element).close();
            }
        }, 5000);
    });
}
