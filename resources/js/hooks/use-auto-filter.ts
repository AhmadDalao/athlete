import { router } from '@inertiajs/react';
import { useEffect, useRef } from 'react';

type FilterValue = string | number | boolean | null | undefined;

interface UseAutoFilterOptions {
    url: string;
    payload: Record<string, FilterValue>;
    only?: string[];
    debounceMs?: number;
    enabled?: boolean;
}

export function useAutoFilter({ url, payload, only, debounceMs = 180, enabled = true }: UseAutoFilterOptions) {
    const didHydrate = useRef(false);
    const onlyKey = only?.join('|') ?? '';
    const payloadKey = JSON.stringify(payload);

    useEffect(() => {
        if (!enabled) {
            return;
        }

        if (!didHydrate.current) {
            didHydrate.current = true;

            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(url, JSON.parse(payloadKey) as Record<string, FilterValue>, {
                only: onlyKey ? onlyKey.split('|') : undefined,
                preserveScroll: true,
                preserveState: true,
                replace: true,
            });
        }, debounceMs);

        return () => window.clearTimeout(timeout);
    }, [debounceMs, enabled, onlyKey, payloadKey, url]);
}
