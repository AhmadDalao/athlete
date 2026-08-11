const labels = {
    system: 'System',
    dark: 'Dark',
    light: 'Light',
};

const icons = {
    system: 'fa-display',
    dark: 'fa-moon',
    light: 'fa-sun',
};

const resolveTheme = (preference) => {
    if (preference !== 'system') {
        return preference;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};

const renderTheme = (preference) => {
    const resolved = resolveTheme(preference);
    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.theme = resolved;
    document.querySelector('meta[name="theme-color"]')?.setAttribute(
        'content',
        resolved === 'dark' ? '#090d0b' : '#f4f5ef',
    );

    document.querySelectorAll('[data-theme-label]').forEach((node) => {
        node.textContent = labels[preference];
    });

    document.querySelectorAll('[data-theme-icon]').forEach((node) => {
        node.className = `fa-solid ${icons[preference]}`;
    });

    document.querySelectorAll('[data-theme-option]').forEach((button) => {
        button.classList.toggle('active', button.dataset.themeOption === preference);
    });
};

const saveAuthenticatedPreference = async (preference) => {
    const endpoint = document.documentElement.dataset.themeEndpoint;

    if (!endpoint) {
        return;
    }

    const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ theme: preference }),
    });

    if (!response.ok) {
        throw new Error('Theme preference could not be saved.');
    }
};

export const initializeTheme = (root = document) => {
    root.querySelectorAll('[data-theme-option]').forEach((button) => {
        if (button.dataset.themeReady === 'true') {
            return;
        }

        button.dataset.themeReady = 'true';
        button.addEventListener('click', async () => {
            const preference = button.dataset.themeOption;
            localStorage.setItem('throughline-theme', preference);
            renderTheme(preference);

            try {
                await saveAuthenticatedPreference(preference);
            } catch (error) {
                console.warn(error.message);
            }
        });
    });
};

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (document.documentElement.dataset.themePreference === 'system') {
        renderTheme('system');
    }
});
