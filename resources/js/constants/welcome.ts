import {
    Building2,
    Compass,
    Globe2,
    LineChart,
    Layers,
    MapPin,
    Network,
    ShieldCheck,
    Target,
    Workflow,
} from 'lucide-react';

/**
 * Firm content for the BlackDesck Agency landing page.
 * Source of truth: the firm's positioning as a cross-border strategic advisory
 * practice operating across the United States, the UAE, and Morocco.
 */

/** Core services — the three headline offerings. */
export const SERVICES = [
    {
        icon: Compass,
        title: 'Strategic Advisory',
        body: 'Cross-border growth strategy and structured business expansion support for companies, investors, and growth-focused organizations.',
    },
    {
        icon: Workflow,
        title: 'Operational Execution',
        body: 'Coordinated execution support for regional and international initiatives — from planning through delivery and oversight.',
    },
    {
        icon: Globe2,
        title: 'Regional Access',
        body: 'Strategic positioning across the GCC and Moroccan markets through a selective network and on-the-ground presence.',
    },
] as const;

/** Expertise areas — the firm's core capabilities. */
export const EXPERTISE = [
    {
        icon: Target,
        title: 'Strategic Development',
        body: 'International growth strategy, commercial partnerships, and expansion structuring.',
    },
    {
        icon: Network,
        title: 'Cross-Border Execution',
        body: 'Operational coordination, execution support, and project oversight across jurisdictions.',
    },
    {
        icon: LineChart,
        title: 'Market Expansion',
        body: 'Regional expansion, business coordination, and strategic positioning in new markets.',
    },
] as const;

/** Sector focus — areas the firm operates across. */
export const SECTORS = [
    'Technology',
    'Infrastructure',
    'Private Investment',
    'Growth Ventures',
    'Africa & Emerging Markets',
    'US–MENA Bridge',
    'Diaspora Investment',
    'Energy & Resources',
    'Financial Services',
] as const;

/** Approach — the firm's four-stage engagement model. */
export const APPROACH = [
    {
        title: 'Strategic Alignment',
        phase: 'Discovery & Positioning',
        body: 'We align on objectives, constraints, and the markets in play before any commitment is made.',
    },
    {
        title: 'Structuring',
        phase: 'Planning & Coordination',
        body: 'We structure the engagement, the commercial approach, and the regional coordination required to execute.',
    },
    {
        title: 'Execution',
        phase: 'Operational Support',
        body: 'We coordinate delivery on the ground, supporting partners and initiatives across borders.',
    },
    {
        title: 'Oversight',
        phase: 'Reporting & Monitoring',
        body: 'We monitor progress and report transparently, keeping every stakeholder informed throughout.',
    },
] as const;

/** Operational presence — the firm's three regional hubs. */
export const REGIONS = [
    {
        country: 'United Arab Emirates',
        city: 'Sharjah — Regional hub',
        coords: '25.36° N / 55.39° E' as string | null,
        image: '/assets/images/landing/region-uae.jpg',
        body: 'Regional operations hub supporting GCC market access, business coordination, and strategic partnerships.',
        note: 'BlackDesck Agency FZE — SRTIP No. 9731' as string | null,
    },
    {
        country: 'United States',
        city: 'Strategic coordination',
        coords: null as string | null,
        image: '/assets/images/landing/region-us.jpg',
        body: 'Strategic business coordination and international commercial structuring.',
        note: null as string | null,
    },
    {
        country: 'Morocco',
        city: 'Rabat',
        coords: '34.02° N / 6.84° W' as string | null,
        image: '/assets/images/landing/region-morocco.jpg',
        body: 'Strategic business development and expansion support across North Africa.',
        note: null as string | null,
    },
] as const;

/** Firm figures — the stat band on the landing page. */
export const FIGURES = [
    { value: '03', label: 'Operational regions' },
    { value: '04', label: 'Engagement stages' },
    { value: '09', label: 'Sector practices' },
    { value: '01', label: 'Accountable partner' },
] as const;

/** Why the firm — differentiators. */
export const VALUES = [
    {
        icon: ShieldCheck,
        title: 'Selective network',
        body: 'Engagements are supported through a curated network of vetted operators and partners.',
    },
    {
        icon: MapPin,
        title: 'Regional presence',
        body: 'On-the-ground coordination across the US, the UAE, and Morocco — not remote introductions.',
    },
    {
        icon: Layers,
        title: 'End-to-end ownership',
        body: 'From strategy through execution and oversight, the firm owns the engagement and the outcome.',
    },
    {
        icon: Building2,
        title: 'Institutional rigor',
        body: 'Structured, transparent processes built for investors and growth-focused organizations.',
    },
] as const;

export const FAQS = [
    {
        q: 'Who does BlackDesck work with?',
        a: 'We advise companies, investors, and growth-focused organizations pursuing international expansion, strategic partnerships, and operational execution across the United States, the UAE, and Morocco.',
    },
    {
        q: 'How does an engagement begin?',
        a: 'Every engagement starts with a consultation. We align on your objectives and the markets in play, then structure a scope and approach before any commitment is made.',
    },
    {
        q: 'Which markets do you cover?',
        a: 'We operate at the US–MENA bridge, with a regional hub in Sharjah (UAE) for GCC access, a presence in Rabat (Morocco) for North Africa, and strategic coordination from the United States.',
    },
    {
        q: 'How do you manage cross-border execution?',
        a: 'Through a selective network and regional operational presence, we coordinate delivery on the ground — supporting partners and initiatives across jurisdictions, with transparent reporting throughout.',
    },
    {
        q: 'How do I schedule a consultation?',
        a: 'Select an engagement below or schedule a consultation directly. Payment is processed securely, and we provision a private client portal so you can track progress at every stage.',
    },
] as const;
