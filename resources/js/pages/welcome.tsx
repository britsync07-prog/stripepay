import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    ArrowUpRight,
    Instagram,
    Linkedin,
    Mail,
    Plus,
} from 'lucide-react';
import {
    AnimatePresence,
    motion,
    useReducedMotion,
    useScroll,
    useSpring,
} from 'motion/react';
import type { Variants } from 'motion/react';
import { useState } from 'react';
import type { ReactNode } from 'react';

import { ChatWidget } from '@/components/chat/chat-widget';
import { CropMarks } from '@/components/guest/crop-marks';
import { Button } from '@/components/ui/button';
import { EASE_SPRING } from '@/constants/motion';
import {
    APPROACH,
    EXPERTISE,
    FAQS,
    FIGURES,
    REGIONS,
    SECTORS,
    SERVICES,
    VALUES,
} from '@/constants/welcome';
import { cn } from '@/lib/utils';
import { customPlan, dashboard, login } from '@/routes';
import { ask as conciergeAsk } from '@/routes/concierge';
import type { Plan } from '@/types';

const NAV = [
    { no: '03', label: 'Expertise', href: '#expertise' },
    { no: '04', label: 'Approach', href: '#approach' },
    { no: '05', label: 'Presence', href: '#presence' },
    { no: '07', label: 'Engagements', href: '#engagements' },
];

function SectionHead({
    no,
    eyebrow,
    title,
    subtitle,
    tone = 'paper',
    reduce,
}: {
    no: string;
    eyebrow: string;
    title: ReactNode;
    subtitle?: string;
    tone?: 'paper' | 'ink';
    reduce: boolean | null;
}) {
    const ink = tone === 'ink';

    return (
        <motion.div
            className="mb-14"
            initial={{ opacity: 0, y: reduce ? 0 : 18 }}
            whileInView={{ opacity: 1, y: 0 }}
            viewport={{ once: true, margin: '0px 0px -10% 0px' }}
            transition={{ duration: 0.6, ease: EASE_SPRING }}
        >
            <div className="flex items-center gap-5">
                <span className="text-kicker text-primary">№&thinsp;{no}</span>
                <span
                    aria-hidden
                    className={cn(
                        'h-px flex-1',
                        ink
                            ? 'bg-gradient-to-r from-background/25 to-transparent'
                            : 'bg-gradient-to-r from-border to-transparent',
                    )}
                />
                <span
                    className={cn(
                        'text-eyebrow',
                        ink ? 'text-background/50' : 'text-brand-accent',
                    )}
                >
                    {eyebrow}
                </span>
            </div>
            <div className="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr] lg:items-end">
                <h2 className="font-serif text-3xl leading-[1.06] font-semibold tracking-tight text-balance sm:text-4xl md:text-[2.65rem]">
                    {title}
                </h2>
                {subtitle && (
                    <p
                        className={cn(
                            'max-w-md text-[15px] leading-relaxed lg:justify-self-end lg:text-right',
                            ink
                                ? 'text-background/60'
                                : 'text-muted-foreground',
                        )}
                    >
                        {subtitle}
                    </p>
                )}
            </div>
        </motion.div>
    );
}

function SectorTicker() {
    const row = [...SECTORS, ...SECTORS];

    return (
        <div
            aria-hidden
            className="group relative overflow-hidden border-y border-border/70 bg-surface/70 py-4"
        >
            <div className="flex w-max animate-marquee group-hover:[animation-play-state:paused]">
                {row.map((sector, i) => (
                    <span
                        key={`${sector}-${i}`}
                        className="flex items-center whitespace-nowrap"
                    >
                        <span className="text-kicker text-muted-foreground">
                            {sector}
                        </span>
                        <span className="mx-7 text-xs text-primary">✦</span>
                    </span>
                ))}
            </div>
        </div>
    );
}

function FaqRow({
    q,
    a,
    index,
    open,
    onToggle,
    reduce,
}: {
    q: string;
    a: string;
    index: number;
    open: boolean;
    onToggle: () => void;
    reduce: boolean | null;
}) {
    return (
        <div className="border-b border-border">
            <button
                type="button"
                onClick={onToggle}
                aria-expanded={open}
                aria-controls={`faq-panel-${index}`}
                className="group flex w-full items-baseline gap-5 py-6 text-left"
            >
                <span className="text-kicker text-brand-accent">
                    Q.0{index + 1}
                </span>
                <span className="flex-1 font-serif text-lg font-medium transition-colors group-hover:text-brand-accent sm:text-xl">
                    {q}
                </span>
                <span
                    className={cn(
                        'flex size-8 shrink-0 translate-y-1.5 items-center justify-center rounded-full border transition-all duration-300',
                        open
                            ? 'rotate-45 border-primary/40 bg-primary/10 text-primary'
                            : 'text-muted-foreground group-hover:border-primary/40 group-hover:text-primary',
                    )}
                >
                    <Plus aria-hidden className="size-4" />
                </span>
            </button>
            <AnimatePresence initial={false}>
                {open && (
                    <motion.div
                        key="content"
                        id={`faq-panel-${index}`}
                        initial={{ height: 0, opacity: 0 }}
                        animate={{ height: 'auto', opacity: 1 }}
                        exit={{ height: 0, opacity: 0 }}
                        transition={{
                            duration: reduce ? 0 : 0.3,
                            ease: EASE_SPRING,
                        }}
                        className="overflow-hidden"
                    >
                        <p className="max-w-2xl pb-7 pl-12 text-[15px] leading-relaxed text-muted-foreground">
                            {a}
                        </p>
                    </motion.div>
                )}
            </AnimatePresence>
        </div>
    );
}

function PlanCard({ plan, index }: { plan: Plan; index: number }) {
    const featured = plan.is_featured;

    return (
        <div
            className={cn(
                'relative flex h-full flex-col p-8',
                featured
                    ? 'rounded-2xl bg-foreground text-background shadow-elevated ring-1 ring-primary/40 lg:-my-5 lg:h-[calc(100%+2.5rem)]'
                    : 'surface-card transition-shadow duration-300 hover:shadow-elevated',
            )}
        >
            {featured && <CropMarks />}

            <div className="flex items-center justify-between gap-3">
                <span
                    className={cn(
                        'text-kicker',
                        featured ? 'text-primary' : 'text-brand-accent',
                    )}
                >
                    ENG—0{index + 1}
                </span>
                {featured && (
                    <span className="inline-flex items-center gap-1.5 rounded-full border border-primary/40 bg-primary/15 px-2.5 py-1 text-microcaps text-primary">
                        <span aria-hidden>✦</span> Most engaged
                    </span>
                )}
            </div>

            <h3 className="mt-5 font-serif text-2xl font-semibold">
                {plan.name}
            </h3>
            <div className="mt-3 flex items-baseline gap-2">
                <span
                    className={cn(
                        'font-mono text-4xl font-bold tracking-tight tabular-nums',
                        featured && 'text-gold-gradient',
                    )}
                >
                    {plan.formatted_price}
                </span>
                <span
                    className={cn(
                        'text-sm',
                        featured
                            ? 'text-background/60'
                            : 'text-muted-foreground',
                    )}
                >
                    {plan.interval_label}
                </span>
            </div>
            {plan.description && (
                <p
                    className={cn(
                        'mt-3 text-sm leading-relaxed',
                        featured
                            ? 'text-background/70'
                            : 'text-muted-foreground',
                    )}
                >
                    {plan.description}
                </p>
            )}

            <ul className="mt-6 flex-1">
                {plan.features.map((feature) => (
                    <li
                        key={feature}
                        className={cn(
                            'flex items-start gap-3 border-b py-2.5 text-sm last:border-0',
                            featured
                                ? 'border-background/10'
                                : 'border-border/60',
                        )}
                    >
                        <span
                            aria-hidden
                            className="mt-1 text-[10px] text-primary"
                        >
                            ✦
                        </span>
                        <span>{feature}</span>
                    </li>
                ))}
            </ul>

            {plan.outcome && (
                <p
                    className={cn(
                        'mt-6 border-l-2 border-primary/50 pl-4 font-serif text-[15px] italic',
                        featured
                            ? 'text-background/70'
                            : 'text-muted-foreground',
                    )}
                >
                    “{plan.outcome}”
                </p>
            )}

            <Button
                asChild
                size="lg"
                className={cn(
                    'group/cta mt-7 w-full',
                    !featured &&
                        'border-foreground/20 hover:border-primary/50 hover:bg-primary/10 hover:text-foreground',
                )}
                variant={featured ? 'default' : 'outline'}
            >
                {/* TEMPORARY: route plan CTAs to the custom request (no payment) instead
                    of the checkout flow. Revert to `buy(plan.slug)` to re-enable payments. */}
                <Link href={customPlan().url}>
                    {plan.cta_label}
                    <ArrowRight className="size-4 transition-transform group-hover/cta:translate-x-0.5" />
                </Link>
            </Button>
        </div>
    );
}

export default function Welcome({ plans }: { plans: Plan[] }) {
    const { auth, aiEnabled } = usePage().props;
    const reduce = useReducedMotion();
    const rise = reduce ? 0 : 18;
    const [openFaq, setOpenFaq] = useState<number | null>(0);

    const { scrollYProgress } = useScroll();
    const progressX = useSpring(scrollYProgress, {
        stiffness: 120,
        damping: 30,
        mass: 0.3,
    });

    const group: Variants = {
        hidden: {},
        show: { transition: { staggerChildren: 0.1, delayChildren: 0.05 } },
    };
    const item: Variants = {
        hidden: { opacity: 0, y: reduce ? 0 : 22 },
        show: {
            opacity: 1,
            y: 0,
            transition: { duration: 0.55, ease: EASE_SPRING },
        },
    };

    const [hub, ...satellites] = REGIONS;

    return (
        <>
            <Head title="BlackDesck Agency — Strategic Expansion. Cross-Border Execution." />
            <div className="min-h-screen bg-background text-foreground">
                <motion.div
                    aria-hidden
                    className="fixed inset-x-0 top-0 z-50 h-0.5 origin-left bg-gradient-to-r from-primary via-brand-accent to-primary"
                    style={{ scaleX: progressX }}
                />

                {/* Header */}
                <header className="sticky top-0 z-30 border-b border-border/60 bg-background/85 backdrop-blur-xl">
                    <div className="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3.5 sm:px-6">
                        <Link
                            href="/"
                            aria-label="BlackDesck Agency — home"
                            className="shrink-0 transition-transform duration-200 hover:scale-[1.02]"
                        >
                            <img
                                src="/assets/images/logoBA-ink.png"
                                alt="BlackDesck Agency"
                                className="h-9 w-auto sm:h-11"
                            />
                        </Link>
                        <nav
                            aria-label="Sections"
                            className="hidden items-center gap-7 lg:flex"
                        >
                            {NAV.map((n) => (
                                <a
                                    key={n.href}
                                    href={n.href}
                                    className="group text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    <span
                                        aria-hidden
                                        className="mr-1.5 text-microcaps text-primary/70 transition-colors group-hover:text-primary"
                                    >
                                        {n.no}
                                    </span>
                                    {n.label}
                                </a>
                            ))}
                        </nav>
                        <nav className="flex shrink-0 items-center gap-2">
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="hidden sm:inline-flex"
                            >
                                <Link href={customPlan()}>Custom plan</Link>
                            </Button>
                            {auth.user ? (
                                <Button asChild size="sm">
                                    <Link href={dashboard()}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button
                                        asChild
                                        variant="ghost"
                                        size="sm"
                                        className="hidden sm:inline-flex"
                                    >
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild size="sm">
                                        <a href="#engagements">
                                            Schedule consultation
                                        </a>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                {/* Hero — asymmetric editorial, ink surface */}
                <section className="relative overflow-hidden bg-foreground text-background">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-guide opacity-25"
                    />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-grain opacity-[0.05] mix-blend-overlay"
                    />
                    <div
                        aria-hidden
                        className="pointer-events-none absolute -top-32 right-0 size-[480px] rounded-full bg-[radial-gradient(circle,var(--primary),transparent_65%)] opacity-15"
                    />

                    <div className="relative mx-auto grid w-full max-w-6xl gap-14 px-6 pt-20 pb-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-20 lg:pt-24 lg:pb-20">
                        <motion.div
                            variants={group}
                            initial="hidden"
                            animate="show"
                        >
                            <motion.p
                                variants={item}
                                className="flex flex-wrap items-center gap-x-3 gap-y-1 text-kicker text-background/60"
                            >
                                United States
                                <span aria-hidden className="text-primary">
                                    ✦
                                </span>
                                United Arab Emirates
                                <span aria-hidden className="text-primary">
                                    ✦
                                </span>
                                Morocco
                            </motion.p>
                            <motion.h1
                                variants={item}
                                className="mt-7 font-serif text-[2.9rem] leading-[1.01] font-semibold tracking-tight text-balance sm:text-6xl lg:text-[4.4rem]"
                            >
                                Strategic Expansion.
                                <br />
                                <em className="text-gold-gradient font-medium">
                                    Cross-Border Execution.
                                </em>
                            </motion.h1>
                            <motion.p
                                variants={item}
                                className="mt-7 max-w-xl text-lg leading-relaxed text-background/70"
                            >
                                BlackDesck Agency advises companies, investors,
                                and growth-focused organizations on
                                international expansion, strategic partnerships,
                                and operational execution at the US–MENA bridge.
                            </motion.p>
                            <motion.div
                                variants={item}
                                className="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center"
                            >
                                <Button
                                    asChild
                                    size="lg"
                                    className="group w-full sm:w-auto"
                                >
                                    <a href="#engagements">
                                        Schedule consultation
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                    </a>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="w-full border-background/30 bg-background/5 text-background backdrop-blur-sm hover:bg-background/15 hover:text-background sm:w-auto"
                                >
                                    <a href="#expertise">Discover expertise</a>
                                </Button>
                            </motion.div>
                            <motion.p
                                variants={item}
                                className="mt-9 border-l border-primary/40 pl-4 text-sm text-background/50 italic"
                            >
                                Every engagement begins with alignment — scope
                                is structured before any commitment is made.
                            </motion.p>
                        </motion.div>

                        <motion.figure
                            className="relative hidden lg:block"
                            initial={{ opacity: 0, y: reduce ? 0 : 28 }}
                            animate={{ opacity: 1, y: 0 }}
                            transition={{
                                duration: 0.8,
                                delay: 0.15,
                                ease: EASE_SPRING,
                            }}
                        >
                            <div className="relative p-3">
                                <CropMarks />
                                <div className="overflow-hidden">
                                    <img
                                        src="/assets/images/landing/hero.jpg"
                                        alt="Cross-border advisory at work"
                                        className="aspect-[4/5] w-full object-cover"
                                    />
                                </div>
                            </div>
                            <figcaption className="mt-3 flex items-center justify-between px-3 text-microcaps text-background/45">
                                <span>Fig. 01 — The US–MENA bridge</span>
                                <span aria-hidden className="text-primary">
                                    ✦
                                </span>
                            </figcaption>
                            <div className="absolute -bottom-6 -left-8 rounded-xl border border-background/15 bg-foreground/90 p-4 shadow-elevated backdrop-blur">
                                <p className="text-gold-gradient font-serif text-3xl font-semibold tabular-nums">
                                    3
                                </p>
                                <p className="mt-0.5 text-microcaps text-background/55">
                                    Operational regions
                                </p>
                            </div>
                        </motion.figure>
                    </div>

                    <div className="relative border-t border-background/10">
                        <div className="mx-auto grid w-full max-w-6xl gap-2 px-6 py-4 sm:grid-cols-3">
                            {REGIONS.map((r) => (
                                <p
                                    key={r.country}
                                    className="flex items-baseline gap-2 text-microcaps text-background/45"
                                >
                                    <span className="text-primary/70">
                                        {r.coords ?? '— · —'}
                                    </span>
                                    {r.country}
                                </p>
                            ))}
                        </div>
                    </div>
                </section>

                <SectorTicker />

                {/* № 01 — What we do: editorial index */}
                <section className="mx-auto w-full max-w-6xl px-6 py-20 sm:py-24">
                    <SectionHead
                        no="01"
                        eyebrow="What we do"
                        title={
                            <>
                                Advisory at the intersection of{' '}
                                <em className="text-gold-gradient">
                                    strategy and execution
                                </em>
                            </>
                        }
                        subtitle="BlackDesck operates where strategic business development, market expansion, and execution advisory meet."
                        reduce={reduce}
                    />
                    <motion.div
                        variants={group}
                        initial="hidden"
                        whileInView="show"
                        viewport={{ once: true, margin: '0px 0px -12% 0px' }}
                    >
                        {SERVICES.map((s, i) => (
                            <motion.article
                                key={s.title}
                                variants={item}
                                className="group grid gap-3 border-t border-border py-8 transition-colors duration-300 last:border-b hover:bg-surface/60 md:grid-cols-[72px_1fr_1.1fr_56px] md:items-center md:gap-8 md:px-4"
                            >
                                <span className="font-mono text-sm text-brand-accent transition-colors group-hover:text-primary">
                                    0{i + 1}
                                </span>
                                <h3 className="font-serif text-2xl font-semibold tracking-tight transition-transform duration-300 group-hover:translate-x-1 sm:text-3xl">
                                    {s.title}
                                </h3>
                                <p className="max-w-md text-[15px] leading-relaxed text-muted-foreground">
                                    {s.body}
                                </p>
                                <span className="hidden size-11 items-center justify-center rounded-full border text-muted-foreground transition-all duration-300 group-hover:border-primary/40 group-hover:bg-primary/10 group-hover:text-primary md:flex">
                                    <s.icon className="size-5" />
                                </span>
                            </motion.article>
                        ))}
                    </motion.div>
                </section>

                {/* № 02 — The firm */}
                <section
                    id="about"
                    className="scroll-mt-20 border-y border-border/60 bg-surface/60"
                >
                    <div className="mx-auto w-full max-w-6xl px-6 py-20 lg:py-24">
                        <SectionHead
                            no="02"
                            eyebrow="The firm"
                            title={
                                <>
                                    A selective network with{' '}
                                    <em className="text-gold-gradient">
                                        regional operational presence
                                    </em>
                                </>
                            }
                            reduce={reduce}
                        />
                        <div className="grid items-start gap-12 lg:grid-cols-2">
                            <motion.div
                                initial={{ opacity: 0, y: rise }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -10% 0px',
                                }}
                                transition={{
                                    duration: 0.6,
                                    ease: EASE_SPRING,
                                }}
                            >
                                <p className="font-serif text-xl leading-relaxed font-medium text-balance sm:text-2xl">
                                    Through a selective network and regional
                                    operational presence, BlackDesck supports
                                    cross-border initiatives between the United
                                    States, the UAE, and Morocco — owning the
                                    engagement from strategy through execution
                                    and oversight.
                                </p>
                                <div className="mt-10">
                                    <p className="text-eyebrow text-muted-foreground">
                                        Sector focus
                                    </p>
                                    <ul className="mt-4 grid grid-cols-1 sm:grid-cols-2">
                                        {SECTORS.map((sector) => (
                                            <li
                                                key={sector}
                                                className="flex items-center gap-3 border-b border-border/70 py-2.5 text-sm font-medium text-foreground/80"
                                            >
                                                <span
                                                    aria-hidden
                                                    className="text-[9px] text-primary"
                                                >
                                                    ✦
                                                </span>
                                                {sector}
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            </motion.div>
                            <motion.figure
                                className="relative lg:pl-6"
                                initial={{
                                    opacity: 0,
                                    scale: reduce ? 1 : 0.97,
                                }}
                                whileInView={{ opacity: 1, scale: 1 }}
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -10% 0px',
                                }}
                                transition={{
                                    duration: 0.7,
                                    ease: EASE_SPRING,
                                }}
                            >
                                <div className="relative p-3">
                                    <CropMarks />
                                    <img
                                        src="/assets/images/landing/towers.jpg"
                                        loading="lazy"
                                        alt="Corporate towers representing institutional advisory"
                                        className="aspect-[4/5] w-full object-cover"
                                    />
                                </div>
                                <figcaption className="mt-3 flex items-center justify-between px-3 text-microcaps text-muted-foreground">
                                    <span>Fig. 02 — Institutional rigor</span>
                                    <span aria-hidden className="text-primary">
                                        ✦
                                    </span>
                                </figcaption>
                                <div className="absolute right-4 -bottom-4 hidden rounded-xl border bg-card p-4 shadow-elevated edge-highlight sm:block">
                                    <p className="text-microcaps text-muted-foreground">
                                        UAE registration
                                    </p>
                                    <p className="mt-1 font-mono text-sm font-semibold">
                                        SRTIP № 9731
                                    </p>
                                </div>
                            </motion.figure>
                        </div>

                        <motion.div
                            className="mt-16 grid grid-cols-2 border-y border-border lg:grid-cols-4"
                            initial={{ opacity: 0, y: rise }}
                            whileInView={{ opacity: 1, y: 0 }}
                            viewport={{ once: true, margin: '0px 0px -8% 0px' }}
                            transition={{ duration: 0.6, ease: EASE_SPRING }}
                        >
                            {FIGURES.map((f, i) => (
                                <div
                                    key={f.label}
                                    className={cn(
                                        'px-6 py-8',
                                        i > 0 && 'border-l border-border',
                                        i >= 2 &&
                                            'border-t border-border lg:border-t-0',
                                        i === 2 && 'border-l-0 lg:border-l',
                                    )}
                                >
                                    <p className="font-serif text-4xl font-semibold tabular-nums sm:text-5xl">
                                        {f.value}
                                        <span
                                            aria-hidden
                                            className="ml-1 align-super text-base text-primary"
                                        >
                                            ✦
                                        </span>
                                    </p>
                                    <p className="mt-2 text-microcaps text-muted-foreground">
                                        {f.label}
                                    </p>
                                </div>
                            ))}
                        </motion.div>
                    </div>
                </section>

                {/* № 03 — Expertise */}
                <section
                    id="expertise"
                    className="mx-auto w-full max-w-6xl scroll-mt-20 px-6 py-20 sm:py-24"
                >
                    <SectionHead
                        no="03"
                        eyebrow="Expertise"
                        title={
                            <>
                                Capabilities built for{' '}
                                <em className="text-gold-gradient">
                                    cross-border growth
                                </em>
                            </>
                        }
                        reduce={reduce}
                    />
                    <motion.div
                        className="grid surface-ledger md:grid-cols-3"
                        variants={group}
                        initial="hidden"
                        whileInView="show"
                        viewport={{ once: true, margin: '0px 0px -12% 0px' }}
                    >
                        {EXPERTISE.map((e, i) => (
                            <motion.div
                                key={e.title}
                                variants={item}
                                className="group bg-card p-8 transition-colors duration-300 hover:bg-accent/50"
                            >
                                <div className="flex items-center justify-between">
                                    <span className="text-kicker text-brand-accent">
                                        CAP—0{i + 1}
                                    </span>
                                    <e.icon
                                        className="size-5 text-primary transition-transform duration-300 group-hover:scale-110"
                                        strokeWidth={1.5}
                                    />
                                </div>
                                <h3 className="mt-8 font-serif text-xl font-semibold">
                                    {e.title}
                                </h3>
                                <p className="mt-2.5 text-sm leading-relaxed text-muted-foreground">
                                    {e.body}
                                </p>
                            </motion.div>
                        ))}
                    </motion.div>
                </section>

                {/* № 04 — Approach */}
                <section
                    id="approach"
                    className="scroll-mt-20 border-y border-border/60 bg-surface/60"
                >
                    <div className="mx-auto w-full max-w-6xl px-6 py-20 lg:py-24">
                        <SectionHead
                            no="04"
                            eyebrow="Approach"
                            title={
                                <>
                                    A structured,{' '}
                                    <em className="text-gold-gradient">
                                        four-stage
                                    </em>{' '}
                                    engagement model
                                </>
                            }
                            subtitle="Every engagement follows a deliberate path — aligned, structured, executed, and monitored with transparent reporting throughout."
                            reduce={reduce}
                        />
                        <div className="grid gap-12 lg:grid-cols-[0.85fr_1.15fr]">
                            <motion.figure
                                className="lg:sticky lg:top-24 lg:self-start"
                                initial={{ opacity: 0, y: rise }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -10% 0px',
                                }}
                                transition={{
                                    duration: 0.6,
                                    ease: EASE_SPRING,
                                }}
                            >
                                <div className="relative p-3">
                                    <CropMarks />
                                    <img
                                        src="/assets/images/landing/strategy.jpg"
                                        loading="lazy"
                                        alt="Strategic planning session"
                                        className="aspect-[16/11] w-full object-cover"
                                    />
                                </div>
                                <figcaption className="mt-3 flex items-center justify-between px-3 text-microcaps text-muted-foreground">
                                    <span>Fig. 03 — Structured delivery</span>
                                    <span aria-hidden className="text-primary">
                                        ✦
                                    </span>
                                </figcaption>
                            </motion.figure>
                            <motion.ol
                                variants={group}
                                initial="hidden"
                                whileInView="show"
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -12% 0px',
                                }}
                            >
                                {APPROACH.map((step, i) => (
                                    <motion.li
                                        key={step.title}
                                        variants={item}
                                        className={cn(
                                            'group grid grid-cols-[88px_1fr] items-start gap-5 py-8 sm:grid-cols-[110px_1fr]',
                                            i > 0 && 'border-t border-border',
                                        )}
                                    >
                                        <span
                                            aria-hidden
                                            className="font-serif text-6xl leading-none font-semibold text-primary/20 italic transition-colors duration-300 select-none group-hover:text-primary/40 sm:text-7xl"
                                        >
                                            {i + 1}
                                        </span>
                                        <div>
                                            <p className="text-eyebrow text-brand-accent">
                                                {step.phase}
                                            </p>
                                            <h3 className="mt-2 font-serif text-xl font-semibold sm:text-2xl">
                                                {step.title}
                                            </h3>
                                            <p className="mt-2 max-w-md text-[15px] leading-relaxed text-muted-foreground">
                                                {step.body}
                                            </p>
                                        </div>
                                    </motion.li>
                                ))}
                            </motion.ol>
                        </div>
                    </div>
                </section>

                {/* № 05 — Presence: asymmetric bento */}
                <section
                    id="presence"
                    className="mx-auto w-full max-w-6xl scroll-mt-20 px-6 py-20 sm:py-24"
                >
                    <SectionHead
                        no="05"
                        eyebrow="Operational presence"
                        title={
                            <>
                                On the ground across{' '}
                                <em className="text-gold-gradient">
                                    three markets
                                </em>
                            </>
                        }
                        subtitle="Regional coordination — not remote introductions — at the US–MENA bridge."
                        reduce={reduce}
                    />
                    <motion.div
                        className="grid gap-6 lg:grid-cols-[1.25fr_1fr]"
                        variants={group}
                        initial="hidden"
                        whileInView="show"
                        viewport={{ once: true, margin: '0px 0px -12% 0px' }}
                    >
                        <motion.article
                            variants={item}
                            className="group relative overflow-hidden surface-card transition-shadow duration-300 hover:shadow-elevated"
                        >
                            <div className="relative aspect-[16/10] overflow-hidden lg:aspect-auto lg:h-[340px]">
                                <img
                                    src={hub.image}
                                    loading="lazy"
                                    alt={`${hub.country} — ${hub.city}`}
                                    className="size-full object-cover transition-transform duration-700 group-hover:scale-105"
                                />
                                <div
                                    aria-hidden
                                    className="absolute inset-0 bg-gradient-to-t from-foreground/80 via-foreground/20 to-transparent"
                                />
                                <span className="absolute top-4 left-4 rounded-full border border-primary/50 bg-foreground/70 px-3 py-1 text-microcaps text-primary backdrop-blur">
                                    Regional hub
                                </span>
                                <div className="absolute bottom-4 left-5 text-background">
                                    <p className="text-microcaps text-primary/90">
                                        {hub.coords}
                                    </p>
                                    <h3 className="mt-1 font-serif text-2xl font-semibold">
                                        {hub.country}
                                    </h3>
                                </div>
                            </div>
                            <div className="p-6">
                                <p className="text-[15px] leading-relaxed text-muted-foreground">
                                    {hub.body}
                                </p>
                                {hub.note && (
                                    <p className="mt-4 border-t border-border/70 pt-3 font-mono text-[11px] text-muted-foreground/80">
                                        {hub.note}
                                    </p>
                                )}
                            </div>
                        </motion.article>

                        <div className="grid gap-6">
                            {satellites.map((r) => (
                                <motion.article
                                    key={r.country}
                                    variants={item}
                                    className="group relative overflow-hidden surface-card transition-shadow duration-300 hover:shadow-elevated"
                                >
                                    <div className="relative aspect-[16/6] overflow-hidden">
                                        <img
                                            src={r.image}
                                            loading="lazy"
                                            alt={`${r.country} — ${r.city}`}
                                            className="size-full object-cover transition-transform duration-700 group-hover:scale-105"
                                        />
                                        <div
                                            aria-hidden
                                            className="absolute inset-0 bg-gradient-to-t from-foreground/75 to-transparent"
                                        />
                                        <div className="absolute bottom-3 left-5 text-background">
                                            <p className="text-microcaps text-primary/90">
                                                {r.coords ?? r.city}
                                            </p>
                                            <h3 className="mt-0.5 font-serif text-xl font-semibold">
                                                {r.country}
                                            </h3>
                                        </div>
                                    </div>
                                    <p className="p-6 text-sm leading-relaxed text-muted-foreground">
                                        {r.body}
                                    </p>
                                </motion.article>
                            ))}
                        </div>
                    </motion.div>
                </section>

                {/* Why BlackDesck — ink band */}
                <section className="relative overflow-hidden bg-foreground text-background">
                    <div
                        aria-hidden
                        className="pointer-events-none absolute inset-0 bg-guide opacity-20"
                    />
                    <div className="relative mx-auto w-full max-w-6xl px-6 py-20 sm:py-24">
                        <SectionHead
                            no="06"
                            eyebrow="Why BlackDesck"
                            tone="ink"
                            title={
                                <>
                                    A partner{' '}
                                    <em className="text-gold-gradient">
                                        accountable
                                    </em>{' '}
                                    for the outcome
                                </>
                            }
                            reduce={reduce}
                        />
                        <motion.div
                            className="grid border-y border-background/15 sm:grid-cols-2 lg:grid-cols-4"
                            variants={group}
                            initial="hidden"
                            whileInView="show"
                            viewport={{
                                once: true,
                                margin: '0px 0px -12% 0px',
                            }}
                        >
                            {VALUES.map((value, i) => (
                                <motion.div
                                    key={value.title}
                                    variants={item}
                                    className={cn(
                                        'group px-7 py-10 transition-colors duration-300 hover:bg-background/5',
                                        i > 0 &&
                                            'border-t border-background/15 sm:border-t-0 sm:border-l',
                                        i >= 2 && 'sm:border-t lg:border-t-0',
                                        i === 2 && 'sm:border-l-0 lg:border-l',
                                    )}
                                >
                                    <value.icon
                                        className="size-6 text-primary transition-transform duration-300 group-hover:scale-110"
                                        strokeWidth={1.5}
                                    />
                                    <h3 className="mt-6 font-serif text-lg font-semibold">
                                        {value.title}
                                    </h3>
                                    <p className="mt-2.5 text-sm leading-relaxed text-background/60">
                                        {value.body}
                                    </p>
                                </motion.div>
                            ))}
                        </motion.div>
                    </div>
                </section>

                {/* № 07 — Engagements (plans) */}
                {plans.length > 0 && (
                    <section
                        id="engagements"
                        className="mx-auto w-full max-w-6xl scroll-mt-20 px-6 py-20 sm:py-28"
                    >
                        <SectionHead
                            no="07"
                            eyebrow="Engagements"
                            title={
                                <>
                                    Begin with a{' '}
                                    <em className="text-gold-gradient">
                                        defined engagement
                                    </em>
                                </>
                            }
                            subtitle="Transparent, fixed-scope engagements. Schedule securely and we structure the approach before any work begins."
                            reduce={reduce}
                        />
                        <motion.div
                            className="grid items-stretch gap-6 md:grid-cols-3 lg:gap-8"
                            variants={group}
                            initial="hidden"
                            whileInView="show"
                            viewport={{
                                once: true,
                                margin: '0px 0px -12% 0px',
                            }}
                        >
                            {plans.map((plan, i) => (
                                <motion.div
                                    key={plan.id}
                                    variants={item}
                                    className="h-full"
                                >
                                    <PlanCard plan={plan} index={i} />
                                </motion.div>
                            ))}
                        </motion.div>

                        <p className="mt-12 text-center text-sm text-muted-foreground">
                            Need something tailored to your situation?{' '}
                            <Link
                                href={customPlan()}
                                className="font-medium text-brand-accent underline-offset-4 hover:text-primary hover:underline"
                            >
                                Request a custom plan →
                            </Link>
                        </p>
                    </section>
                )}

                {/* № 08 — FAQ */}
                <section className="border-t border-border/60">
                    <div className="mx-auto w-full max-w-6xl px-6 py-20 sm:py-24">
                        <div className="grid gap-12 lg:grid-cols-[0.8fr_1.2fr]">
                            <motion.div
                                initial={{ opacity: 0, y: rise }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -10% 0px',
                                }}
                                transition={{
                                    duration: 0.6,
                                    ease: EASE_SPRING,
                                }}
                                className="lg:sticky lg:top-28 lg:self-start"
                            >
                                <div className="flex items-center gap-5">
                                    <span className="text-kicker text-primary">
                                        №&thinsp;08
                                    </span>
                                    <span className="text-eyebrow text-brand-accent">
                                        FAQ
                                    </span>
                                </div>
                                <h2 className="mt-6 font-serif text-3xl leading-[1.06] font-semibold tracking-tight sm:text-4xl">
                                    Questions,{' '}
                                    <em className="text-gold-gradient">
                                        answered
                                    </em>
                                </h2>
                                <p className="mt-4 max-w-sm text-[15px] text-muted-foreground">
                                    Anything else? Reach the firm directly at{' '}
                                    <a
                                        href="mailto:info@blackdesck.com"
                                        className="font-medium text-brand-accent underline-offset-4 hover:underline"
                                    >
                                        info@blackdesck.com
                                    </a>
                                    .
                                </p>
                            </motion.div>
                            <motion.div
                                className="border-t border-border"
                                initial={{ opacity: 0, y: rise }}
                                whileInView={{ opacity: 1, y: 0 }}
                                viewport={{
                                    once: true,
                                    margin: '0px 0px -10% 0px',
                                }}
                                transition={{
                                    duration: 0.6,
                                    ease: EASE_SPRING,
                                }}
                            >
                                {FAQS.map((faq, i) => (
                                    <FaqRow
                                        key={faq.q}
                                        q={faq.q}
                                        a={faq.a}
                                        index={i}
                                        open={openFaq === i}
                                        onToggle={() =>
                                            setOpenFaq(openFaq === i ? null : i)
                                        }
                                        reduce={reduce}
                                    />
                                ))}
                            </motion.div>
                        </div>
                    </div>
                </section>

                {/* Contact CTA */}
                <section className="px-6 pb-24">
                    <motion.div
                        className="relative mx-auto w-full max-w-5xl overflow-hidden rounded-3xl border border-border/60 bg-foreground px-6 py-16 text-center text-background sm:px-10 sm:py-20"
                        initial={{ opacity: 0, y: reduce ? 0 : 24 }}
                        whileInView={{ opacity: 1, y: 0 }}
                        viewport={{ once: true, margin: '0px 0px -10% 0px' }}
                        transition={{ duration: 0.6, ease: EASE_SPRING }}
                    >
                        <img
                            src="/assets/images/landing/partnership.jpg"
                            loading="lazy"
                            alt=""
                            aria-hidden
                            className="absolute inset-0 size-full object-cover opacity-15"
                        />
                        <div
                            aria-hidden
                            className="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_80%_at_50%_0%,var(--primary),transparent_60%)] opacity-15"
                        />
                        <div className="absolute inset-4">
                            <CropMarks className="size-4" />
                        </div>
                        <div className="relative">
                            <p className="text-kicker text-primary">
                                Begin the conversation
                            </p>
                            <h2 className="mx-auto mt-5 max-w-2xl font-serif text-3xl leading-[1.08] font-semibold tracking-tight text-balance sm:text-[2.75rem]">
                                Considering expansion across the{' '}
                                <em className="text-gold-gradient">
                                    US, GCC, or North Africa
                                </em>
                                ?
                            </h2>
                            <p className="mx-auto mt-5 max-w-xl text-background/70">
                                Schedule a consultation to align on objectives
                                and the markets in play. We structure the
                                approach before any commitment is made.
                            </p>
                            <div className="mt-9 flex flex-col items-center justify-center gap-3 sm:flex-row">
                                <Button
                                    asChild
                                    size="lg"
                                    className="group w-full sm:w-auto"
                                >
                                    <a href="#engagements">
                                        Schedule consultation
                                        <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                    </a>
                                </Button>
                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="w-full border-background/30 bg-background/5 text-background hover:bg-background/15 hover:text-background sm:w-auto"
                                >
                                    <a href="mailto:info@blackdesck.com">
                                        <Mail className="size-4" />{' '}
                                        info@blackdesck.com
                                    </a>
                                </Button>
                            </div>
                            <div className="mx-auto mt-10 max-w-xs border-t border-background/15 pt-6">
                                <p className="font-serif text-xl font-medium text-background/90 italic">
                                    Fadoua Fakir
                                </p>
                                <p className="mt-1 text-microcaps text-background/50">
                                    Managing Director — BlackDesck Agency
                                </p>
                            </div>
                        </div>
                    </motion.div>
                </section>

                {/* Footer */}
                <footer className="overflow-hidden border-t border-border/60 bg-surface/40">
                    <div className="mx-auto w-full max-w-6xl px-6 pt-12">
                        <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <img
                                    src="/assets/images/logoBA-ink.png"
                                    alt="BlackDesck Agency"
                                    className="h-10 w-auto"
                                />
                                <p className="mt-4 max-w-xs text-sm text-muted-foreground">
                                    Strategic Expansion. Cross-Border Execution.
                                    Advisory across the United States, the UAE,
                                    and Morocco.
                                </p>
                                <div className="mt-5 flex items-center gap-2">
                                    <a
                                        href="https://www.linkedin.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="LinkedIn"
                                        className="flex size-9 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:border-primary/40 hover:bg-primary/10 hover:text-primary"
                                    >
                                        <Linkedin className="size-4" />
                                    </a>
                                    <a
                                        href="https://www.instagram.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Instagram"
                                        className="flex size-9 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:border-primary/40 hover:bg-primary/10 hover:text-primary"
                                    >
                                        <Instagram className="size-4" />
                                    </a>
                                </div>
                            </div>
                            <FooterCol
                                title="Navigate"
                                links={[
                                    { label: 'Expertise', href: '#expertise' },
                                    { label: 'Approach', href: '#approach' },
                                    { label: 'Presence', href: '#presence' },
                                    {
                                        label: 'Engagements',
                                        href: '#engagements',
                                    },
                                ]}
                            />
                            <FooterCol
                                title="Operations"
                                links={[
                                    {
                                        label: 'United States',
                                        href: '#presence',
                                    },
                                    {
                                        label: 'UAE — Sharjah',
                                        href: '#presence',
                                    },
                                    {
                                        label: 'Morocco — Rabat',
                                        href: '#presence',
                                    },
                                ]}
                            />
                            <FooterCol
                                title="Contact"
                                links={[
                                    {
                                        label: 'blackdesck.agency',
                                        href: 'https://blackdesck.agency/',
                                    },
                                    {
                                        label: 'info@blackdesck.com',
                                        href: 'mailto:info@blackdesck.com',
                                    },
                                    {
                                        label: 'Schedule consultation',
                                        href: '#engagements',
                                    },
                                ]}
                            />
                        </div>
                        <div className="mt-10 flex flex-col items-center justify-between gap-3 border-t border-border/60 pt-6 text-xs text-muted-foreground sm:flex-row">
                            <span>
                                © {new Date().getFullYear()} BlackDesck Agency.
                                All rights reserved.
                            </span>
                            <span className="flex items-center gap-4 text-kicker">
                                <span>Privacy</span>
                                <span aria-hidden className="text-primary">
                                    ✦
                                </span>
                                <span>Terms</span>
                                <span aria-hidden className="text-primary">
                                    ✦
                                </span>
                                <span>Compliance</span>
                            </span>
                        </div>
                        <p
                            aria-hidden
                            className="pointer-events-none -mb-[0.24em] text-center font-serif text-[clamp(3.5rem,15vw,11.5rem)] leading-none font-semibold tracking-tight whitespace-nowrap text-foreground/[0.05] select-none"
                        >
                            BlackDesck
                        </p>
                    </div>
                </footer>
            </div>

            {aiEnabled && (
                <ChatWidget
                    endpoint={conciergeAsk().url}
                    title="Ask us anything"
                    greeting="Hi! Ask me about our plans and services, and I'll point you to the right starting point."
                    suggestions={[
                        'Which plan fits a company expanding into the UAE?',
                        'What does the International Business Expansion plan include?',
                        'I need something custom — what are my options?',
                    ]}
                />
            )}
        </>
    );
}

function FooterCol({
    title,
    links,
}: {
    title: string;
    links: { label: string; href: string }[];
}) {
    return (
        <div>
            <p className="text-eyebrow text-foreground">{title}</p>
            <ul className="mt-4 space-y-2.5">
                {links.map((l) => {
                    const isExternal = /^https?:\/\//.test(l.href);

                    return (
                        <li key={l.label}>
                            <a
                                href={l.href}
                                {...(isExternal && {
                                    target: '_blank',
                                    rel: 'noopener noreferrer',
                                })}
                                className="group inline-flex items-center gap-1 text-sm text-muted-foreground transition-colors hover:text-primary"
                            >
                                {l.label}
                                <ArrowUpRight className="size-3 opacity-0 transition-opacity group-hover:opacity-100" />
                            </a>
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}
