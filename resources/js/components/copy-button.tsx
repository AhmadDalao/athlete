import { Button } from '@/components/ui/button';
import { Check, Copy } from 'lucide-react';
import { useState } from 'react';

export default function CopyButton({
    value,
    label = 'Copy',
    variant = 'outline',
    className,
}: {
    value: string;
    label?: string;
    variant?: 'default' | 'outline' | 'secondary' | 'ghost' | 'link';
    className?: string;
}) {
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        try {
            await navigator.clipboard.writeText(value);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1800);
        } catch {
            setCopied(false);
        }
    };

    return (
        <Button type="button" variant={variant} size="sm" className={className} onClick={handleCopy}>
            {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
            {copied ? 'Copied' : label}
        </Button>
    );
}
