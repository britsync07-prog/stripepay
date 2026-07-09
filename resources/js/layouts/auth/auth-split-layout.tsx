import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col overflow-hidden bg-brand p-10 text-brand-foreground lg:flex">
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-guide opacity-60"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_30%_0%,var(--primary),transparent_60%)] opacity-15"
                />
                <div
                    aria-hidden
                    className="pointer-events-none absolute inset-0 bg-grain opacity-[0.07] mix-blend-overlay"
                />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <span className="mr-2 flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <AppLogoIcon className="size-5 fill-current" />
                    </span>
                    {name}
                </Link>
                <div className="relative z-20 mt-auto">
                    <p className="font-serif text-2xl leading-snug font-medium text-balance">
                        Expert guidance for global growth.
                    </p>
                    <p className="mt-2 text-sm text-brand-foreground/60">
                        Vetted experts. Managed engagements. Secure payments.
                    </p>
                </div>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="h-10 fill-current text-foreground sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        <h1 className="text-xl font-medium">{title}</h1>
                        <p className="text-sm text-balance text-muted-foreground">
                            {description}
                        </p>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
