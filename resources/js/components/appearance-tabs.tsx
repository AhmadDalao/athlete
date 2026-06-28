import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    return (
        <div
            className={cn(
                'inline-flex rounded-[1.1rem] border border-stone-200/80 bg-white/88 p-1.5 shadow-[0_18px_40px_-30px_rgba(15,23,42,0.25)]',
                className,
            )}
            {...props}
        >
            <button
                onClick={() => updateAppearance('light')}
                className={cn(
                    'flex items-center rounded-[0.9rem] px-4 py-2 text-sm font-medium transition-colors',
                    appearance === 'light'
                        ? 'bg-[linear-gradient(135deg,rgba(255,247,237,0.95),rgba(236,253,245,0.95))] text-stone-950'
                        : 'text-stone-500',
                )}
            >
                <Sun className="-ml-1 h-4 w-4" />
                <span className="ml-1.5">Light only</span>
            </button>
        </div>
    );
}
