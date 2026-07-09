<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Enums\LeadStage;
use App\Models\ConsultationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin');
        Role::findOrCreate('client');
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function customLead(array $attributes = []): ConsultationRequest
    {
        return ConsultationRequest::create(array_merge([
            'plan_id' => null,
            'name' => 'Acme Corp',
            'email' => 'buyer@example.com',
            'amount' => 0,
            'currency' => 'USD',
            'status' => ConsultationStatus::Pending,
            'lead_stage' => LeadStage::New,
            'lead_source' => 'form',
        ], $attributes));
    }

    public function test_building_a_quotation_derives_total_from_line_items(): void
    {
        $lead = $this->customLead();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.quotation', $lead), [
                'title' => 'Strategic Business Assessment',
                'scope_items' => ['Discovery Session', 'Growth Analysis', ''],
                'line_items' => [
                    ['description' => 'Strategic Business Assessment', 'quantity' => 1, 'unit_amount' => '2000'],
                    ['description' => 'Follow-up review', 'quantity' => 2, 'unit_amount' => '250.50'],
                ],
                'expires_in_days' => 30,
                'delivery_timeline' => '1–2 weeks from payment',
            ])
            ->assertRedirect();

        $lead->refresh();

        // 2000.00 + 2 × 250.50 = 2501.00 → 250100 minor units (no float drift).
        $this->assertSame(250100, (int) $lead->amount);
        $this->assertSame('Strategic Business Assessment', $lead->title);
        // Blank scope rows are dropped.
        $this->assertSame(['Discovery Session', 'Growth Analysis'], $lead->scope_items);
        $this->assertSame(200000, (int) $lead->line_items[0]['unit_amount']);
        $this->assertTrue($lead->isQuotation());
        // Pricing advances the lead to Quoted.
        $this->assertSame(LeadStage::Quoted, $lead->lead_stage);
    }

    public function test_quote_number_uses_bda_year_sequence_and_is_stable(): void
    {
        $year = date('Y');

        $first = $this->customLead();
        $this->actingAs($this->admin)->post(route('admin.consultations.quotation', $first), [
            'line_items' => [['description' => 'A', 'quantity' => 1, 'unit_amount' => '100']],
            'expires_in_days' => 30,
        ]);

        $second = $this->customLead(['email' => 'two@example.com']);
        $this->actingAs($this->admin)->post(route('admin.consultations.quotation', $second), [
            'line_items' => [['description' => 'B', 'quantity' => 1, 'unit_amount' => '100']],
            'expires_in_days' => 30,
        ]);

        $this->assertSame("BDA-{$year}-001", $first->refresh()->quote_number);
        $this->assertSame("BDA-{$year}-002", $second->refresh()->quote_number);

        // Re-issuing keeps the original number.
        $this->actingAs($this->admin)->post(route('admin.consultations.quotation', $first), [
            'line_items' => [['description' => 'A', 'quantity' => 3, 'unit_amount' => '100']],
            'expires_in_days' => 30,
        ]);
        $this->assertSame("BDA-{$year}-001", $first->refresh()->quote_number);
        $this->assertSame(30000, (int) $first->amount);
    }

    public function test_flat_pricing_supersedes_a_quotation_and_drops_the_stale_document(): void
    {
        $lead = $this->customLead();
        $this->issue($lead); // $2,000 quotation derived from one line item.

        $this->assertTrue($lead->refresh()->isQuotation());
        $this->assertSame(200000, (int) $lead->amount);

        // Admin opens the in-place "Manage" dialog on the same custom request and
        // sets a flat price of $500 (SetConsultationPrice, posts with `stay`).
        $this->actingAs($this->admin)
            ->post(route('admin.consultations.price', $lead), [
                'amount' => '500',
                'expires_in_days' => 30,
                'stay' => true,
            ])->assertRedirect();

        $lead->refresh();
        $this->assertSame(50000, (int) $lead->amount);

        // INVARIANT: a live quotation's charged total must always equal the sum of
        // its displayed line items — otherwise the document contradicts itself.
        if ($lead->isQuotation()) {
            $sum = collect($lead->line_items)
                ->sum(fn (array $i) => (int) $i['unit_amount'] * (int) $i['quantity']);

            $this->assertSame((int) $lead->amount, (int) $sum,
                'A live quotation must have amount == sum(line_items).');
        }

        // The public quotation document must no longer render a stale total.
        $this->get(route('consultation.quote', $lead))->assertNotFound();
    }

    public function test_zero_total_quotation_is_rejected(): void
    {
        $lead = $this->customLead();

        // An all-zero total would be unpayable (checkout 404s a $0 custom request).
        $this->actingAs($this->admin)
            ->post(route('admin.consultations.quotation', $lead), [
                'line_items' => [['description' => 'Freebie', 'quantity' => 2, 'unit_amount' => '0']],
                'expires_in_days' => 30,
            ])
            ->assertSessionHasErrors('line_items');

        $this->assertFalse($lead->refresh()->isQuotation());
    }

    public function test_line_items_are_required(): void
    {
        $lead = $this->customLead();

        $this->actingAs($this->admin)
            ->post(route('admin.consultations.quotation', $lead), [
                'line_items' => [],
                'expires_in_days' => 30,
            ])
            ->assertSessionHasErrors('line_items');

        $this->assertFalse($lead->refresh()->isQuotation());
    }

    public function test_public_quotation_page_renders_the_document(): void
    {
        $lead = $this->customLead();
        $this->issue($lead);

        $this->get(route('consultation.quote', $lead))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/quotation')
                ->where('quotation.number', $lead->refresh()->quote_number)
                ->where('quotation.total', '$2,000')
                ->has('quotation.lineItems', 1)
                ->where('contact.email', 'buyer@example.com')
            );
    }

    public function test_non_quotation_request_has_no_public_quote_page(): void
    {
        $link = ConsultationRequest::create([
            'plan_id' => null, 'title' => 'Flat link', 'amount' => 5000,
            'currency' => 'USD', 'status' => ConsultationStatus::Pending,
        ]);

        $this->get(route('consultation.quote', $link))->assertNotFound();
    }

    public function test_accepting_updates_details_company_optional_and_redirects_to_checkout(): void
    {
        $lead = $this->customLead();
        $this->issue($lead);

        // Company omitted entirely — must be accepted.
        $this->post(route('consultation.quote.accept', $lead), [
            'name' => 'Jane Client',
            'email' => 'jane@newmail.com',
            'country' => 'US',
        ])->assertRedirect(route('consultation.checkout', $lead));

        $lead->refresh();
        $this->assertSame('Jane Client', $lead->name);
        $this->assertSame('jane@newmail.com', $lead->email);
        $this->assertNull($lead->company);
        // Still unverified/unprovisioned — identity only comes on payment.
        $this->assertNull($lead->user_id);
    }

    public function test_accept_validates_name_and_email(): void
    {
        $lead = $this->customLead();
        $this->issue($lead);

        $this->post(route('consultation.quote.accept', $lead), [
            'name' => '',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['name', 'email']);
    }

    public function test_checkout_carries_the_quotation_scope_and_lines(): void
    {
        $lead = $this->customLead();
        $this->issue($lead);

        $this->get(route('consultation.checkout', $lead))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('consultation/checkout')
                ->where('quotation.number', $lead->refresh()->quote_number)
                ->has('quotation.scopeItems', 1)
                ->has('quotation.lineItems', 1)
            );
    }

    public function test_quotation_pdf_downloads(): void
    {
        $lead = $this->customLead();
        $this->issue($lead);

        $response = $this->get(route('consultation.quote.pdf', $lead));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    /** Issue a simple one-line quotation on the given lead. */
    private function issue(ConsultationRequest $lead): void
    {
        $this->actingAs($this->admin)->post(route('admin.consultations.quotation', $lead), [
            'title' => 'Strategic Business Assessment',
            'scope_items' => ['Discovery Session'],
            'line_items' => [
                ['description' => 'Strategic Business Assessment', 'quantity' => 1, 'unit_amount' => '2000'],
            ],
            'expires_in_days' => 30,
        ]);
    }
}
