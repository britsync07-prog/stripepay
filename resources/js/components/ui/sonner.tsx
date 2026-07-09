import { Toaster as Sonner, type ToasterProps } from 'sonner';

import { useAppearance } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';

function Toaster({ ...props }: ToasterProps) {
    const { appearance } = useAppearance();

    useFlashToast();

    return (
        <Sonner
            theme={appearance}
            position="top-right"
            className="toaster group"
            toastOptions={{
                classNames: {
                    toast: 'group toast !rounded-xl !border-border/70 !shadow-elevated edge-highlight',
                    title: '!font-serif !font-semibold !tracking-tight',
                    description: '!text-muted-foreground',
                    actionButton:
                        '!rounded-lg !bg-primary !text-primary-foreground',
                    cancelButton: '!rounded-lg !bg-muted !text-muted-foreground',
                    success: '[&_[data-icon]]:!text-success',
                    error: '[&_[data-icon]]:!text-destructive',
                    info: '[&_[data-icon]]:!text-primary',
                    warning: '[&_[data-icon]]:!text-warning',
                },
            }}
            style={
                {
                    '--normal-bg': 'var(--card)',
                    '--normal-text': 'var(--card-foreground)',
                    '--normal-border': 'var(--border)',
                } as React.CSSProperties
            }
            {...props}
        />
    );
}

export { Toaster };
