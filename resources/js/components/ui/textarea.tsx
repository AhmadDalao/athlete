import * as React from 'react';

import { cn } from '@/lib/utils';

function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            className={cn(
                'flex min-h-28 w-full rounded-[1.1rem] border border-stone-200/85 bg-white/92 px-4 py-3 text-sm shadow-[inset_0_1px_0_rgba(255,255,255,0.9)] ring-offset-background placeholder:text-stone-400 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-stone-100 disabled:opacity-70',
                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
