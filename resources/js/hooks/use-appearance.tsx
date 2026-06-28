import { useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const applyTheme = () => {
    document.documentElement.classList.remove('dark');
};

export function initializeTheme() {
    localStorage.setItem('appearance', 'light');
    applyTheme();
}

export function useAppearance() {
    const [appearance, setAppearance] = useState<Appearance>('light');

    const updateAppearance = () => {
        setAppearance('light');
        localStorage.setItem('appearance', 'light');
        applyTheme();
    };

    useEffect(() => {
        updateAppearance('light');
    }, []);

    return { appearance, updateAppearance };
}
