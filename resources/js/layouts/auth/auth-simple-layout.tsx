import { Link } from '@inertiajs/react';
import { ArrowLeft, BadgeCheck, Globe, Lock } from 'lucide-react';

import { CropMarks } from '@/components/guest/crop-marks';
import { home, login, register } from '@/routes';
import type { AuthLayoutProps } from '@/types';

const TRUST = [
    { icon: BadgeCheck, label: 'Vetted experts' },
    { icon: Lock, label: 'Secure checkout' },
    { icon: Globe, label: 'Global reach' },
] as const;

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="relative flex min-h-svh flex-col overflow-hidden bg-background">
            {/* Ambient background — warm paper wash + gold halo + grain */}
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-app-ambient"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute inset-x-0 top-0 h-[42rem] bg-[radial-gradient(58%_46%_at_50%_-4%,var(--primary),transparent_70%)] opacity-[0.10]"
            />
            <div
                aria-hidden
                className="pointer-events-none absolute inset-0 bg-grain opacity-[0.05] mix-blend-overlay"
            />
            {/* Concentric guide rings echoing the marketing hero */}
            <div
                aria-hidden
                className="pointer-events-none absolute -top-44 left-1/2 size-[40rem] -translate-x-1/2 rounded-full border border-primary/10"
            >
                <div className="absolute inset-16 rounded-full border border-primary/[0.07]" />
                <div className="absolute inset-36 rounded-full border border-primary/[0.05]" />
            </div>

            {/* Header: back-to-site */}
            <header className="relative z-10 flex items-center justify-between px-5 py-4 sm:px-8">
                <Link
                    href={home()}
                    className="group inline-flex items-center gap-2 rounded-full border border-border/60 bg-card/60 px-3 py-1.5 text-sm font-medium text-muted-foreground backdrop-blur transition-colors hover:border-border hover:text-foreground"
                >
                    <ArrowLeft className="size-4 transition-transform group-hover:-translate-x-0.5" />
                    Back to home
                </Link>
                <span className="hidden items-center gap-1.5 text-microcaps text-muted-foreground sm:flex">
                    <Lock className="size-3.5 text-primary" /> Secured
                </span>
            </header>

            {/* Centered card */}
            <div className="relative z-10 flex flex-1 flex-col items-center justify-center px-5 py-10 sm:px-8">
                <div className="w-full max-w-md animate-fade-up">
                    {/* Logo above the card */}
                    <Link
                        href={home()}
                        aria-label="BlackDesck Agency — home"
                        className="mx-auto mb-8 block w-fit transition-transform duration-200 hover:scale-[1.03]"
                    >
                        <img
                            src="/assets/images/logoBA-ink.png"
                            alt="BlackDesck Agency"
                            className="h-11 w-auto sm:h-12"
                        />
                    </Link>

                    {/* Elevated form surface, dossier-framed */}
                    <div className="relative p-3">
                        <CropMarks />
                        <div className="relative overflow-hidden rounded-2xl border border-border/70 bg-card/90 p-7 shadow-card edge-highlight backdrop-blur-sm sm:p-9">
                            <div
                                aria-hidden
                                className="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/40 to-transparent"
                            />

                            <div className="flex flex-col gap-8">
                                <div className="space-y-3 text-center">
                                    <p className="flex items-center justify-center gap-3 text-microcaps text-primary">
                                        <span
                                            aria-hidden
                                            className="h-px w-8 bg-gradient-to-r from-transparent to-primary/50"
                                        />
                                        Secure access
                                        <span
                                            aria-hidden
                                            className="h-px w-8 bg-gradient-to-l from-transparent to-primary/50"
                                        />
                                    </p>
                                    <h1 className="font-serif text-2xl font-semibold tracking-tight sm:text-[1.75rem]">
                                        {title}
                                    </h1>
                                    {description && (
                                        <p className="text-sm text-balance text-muted-foreground">
                                            {description}
                                        </p>
                                    )}
                                </div>
                                {children}
                            </div>
                        </div>
                    </div>

                    {/* Trust row under the card */}
                    <ul className="mt-7 flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
                        {TRUST.map(({ icon: Icon, label }, i) => (
                            <li key={label} className="flex items-center gap-3">
                                {i > 0 && (
                                    <span
                                        aria-hidden
                                        className="text-[9px] text-primary"
                                    >
                                        ✦
                                    </span>
                                )}
                                <span className="inline-flex items-center gap-1.5 text-microcaps text-muted-foreground">
                                    <Icon className="size-3.5 text-primary" />
                                    {label}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            {/* Footer navigation */}
            <footer className="relative z-10 flex flex-col items-center justify-between gap-3 border-t border-border/60 px-5 py-5 sm:flex-row sm:px-8">
                <p className="text-microcaps text-muted-foreground">
                    © {new Date().getFullYear()} BlackDesck Agency
                </p>
                <nav className="flex items-center gap-4 text-microcaps">
                    <Link
                        href={home()}
                        className="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Home
                    </Link>
                    <span aria-hidden className="text-[9px] text-primary">
                        ✦
                    </span>
                    <Link
                        href={login()}
                        className="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <span aria-hidden className="text-[9px] text-primary">
                        ✦
                    </span>
                    <Link
                        href={register()}
                        className="text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Register
                    </Link>
                </nav>
            </footer>
        </div>
    );
}
