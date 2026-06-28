import { SidebarInset } from '@/components/ui/sidebar';
import * as React from 'react';

interface AppContentProps extends React.ComponentProps<'div'> {
    variant?: 'header' | 'sidebar';
}

export function AppContent({ variant = 'header', children, ...props }: AppContentProps) {
    if (variant === 'sidebar') {
        return (
            <SidebarInset className="bg-white">
                <div className="mx-auto flex min-h-svh w-full max-w-[1540px] min-w-0 flex-1 flex-col px-5 pb-10 md:px-8 lg:px-12" {...props}>
                    {children}
                </div>
            </SidebarInset>
        );
    }

    return (
        <main className="mx-auto flex h-full w-full max-w-7xl min-w-0 flex-1 flex-col gap-4 rounded-xl" {...props}>
            {children}
        </main>
    );
}
