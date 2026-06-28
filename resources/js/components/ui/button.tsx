import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const buttonVariants = cva(
    'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-[1.1rem] text-sm font-semibold tracking-[-0.01em] ring-offset-background transition-all duration-200 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:translate-y-0 disabled:opacity-50 disabled:shadow-none [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
    {
        variants: {
            variant: {
                default:
                    'bg-primary text-primary-foreground shadow-[0_18px_36px_-20px_rgba(15,118,110,0.7)] hover:-translate-y-px hover:bg-primary/92',
                destructive:
                    'bg-destructive text-destructive-foreground shadow-[0_18px_36px_-20px_rgba(220,38,38,0.5)] hover:-translate-y-px hover:bg-destructive/90',
                outline:
                    'border border-stone-200/90 bg-white/92 text-stone-900 shadow-[0_16px_36px_-28px_rgba(15,23,42,0.32)] hover:-translate-y-px hover:bg-stone-50 hover:text-stone-950',
                secondary:
                    'bg-secondary text-secondary-foreground shadow-[0_16px_32px_-26px_rgba(146,64,14,0.28)] hover:-translate-y-px hover:bg-secondary/88',
                ghost: 'text-stone-700 hover:bg-white/80 hover:text-stone-950',
                link: 'text-primary underline-offset-4 hover:underline',
            },
            size: {
                default: 'h-11 px-5 py-2.5',
                sm: 'h-9 rounded-[0.95rem] px-3.5',
                lg: 'h-12 rounded-[1.25rem] px-8',
                icon: 'h-11 w-11 rounded-[1rem]',
            },
        },
        defaultVariants: {
            variant: 'default',
            size: 'default',
        },
    },
);

export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement>, VariantProps<typeof buttonVariants> {
    asChild?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return <Comp className={cn(buttonVariants({ variant, size, className }))} ref={ref} {...props} />;
});
Button.displayName = 'Button';

export { Button, buttonVariants };
