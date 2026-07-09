<?php

namespace Database\Seeders;

use App\Enums\BillingInterval;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'International Business Expansion',
                'slug' => 'international-business-expansion',
                'description' => 'Solutions for companies and entrepreneurs seeking international growth and access to commercial opportunities.',
                'price' => 5_000_00, // $5,000 in minor units
                'currency' => 'USD',
                'billing_interval' => BillingInterval::PerEngagement,
                'features' => [
                    'Strategic project analysis',
                    'Business positioning study',
                    'Commercial opportunity research',
                    'B2B introductions & matchmaking',
                    'Partner search & identification',
                    'Negotiation support',
                    'Professional network development',
                    'Strategic action plan',
                ],
                'outcome' => 'Accelerated commercial growth and access to qualified opportunities',
                'is_featured' => true, // "Most Chosen"
                'sort_order' => 1,
            ],
            [
                'name' => 'Business Setup & Global Mobility',
                'slug' => 'business-setup-global-mobility',
                'description' => 'Designed for entrepreneurs, consultants, and investors establishing international business foundations with credibility and operational capacity.',
                'price' => 7_500_00, // $7,500
                'currency' => 'USD',
                'billing_interval' => BillingInterval::PerEngagement,
                'features' => [
                    'Strategic advisory',
                    'Company incorporation support',
                    'Administrative support & onboarding',
                    'Mobility & residency solutions',
                    'Business structuring & branding',
                    'Pitch deck & corporate presentation',
                    'Business network development',
                    'Business development support',
                ],
                'outcome' => 'A credible professional setup with an international growth strategy',
                'is_featured' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business Development as a Service',
                'slug' => 'business-development-as-a-service',
                'description' => 'Outsourced strategic commercial development for companies seeking deal closure and sustainable growth results.',
                'price' => 12_000_00, // $12,000
                'currency' => 'USD',
                'billing_interval' => BillingInterval::PerMission,
                'features' => [
                    'Commercial strategy',
                    'B2B pipeline creation',
                    'Key account prospecting',
                    'Investor & partner search',
                    'Commercial offer structuring',
                    'Partnership preparation',
                    'Executive networking',
                    'Commercial representation',
                    'Deal negotiation through to closing',
                ],
                'outcome' => 'Qualified opportunity generation and sustainable commercial growth',
                'is_featured' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], [...$plan, 'cta_label' => 'Pay now']);
        }
    }
}
