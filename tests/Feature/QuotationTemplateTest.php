<?php

namespace Tests\Feature;

use App\Enums\ConsultationStatus;
use App\Models\ConsultationRequest;
use App\Models\QuotationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuotationTemplateTest extends TestCase
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

    private function template(array $overrides = []): QuotationTemplate
    {
        return QuotationTemplate::create(array_merge([
            'name' => 'Strategic Business Assessment',
            'title' => 'Strategic Business Assessment',
            'scope_items' => ['Discovery Session', 'Growth Analysis'],
            'line_items' => [
                ['description' => 'Strategic Business Assessment', 'quantity' => 1, 'unit_amount' => 200000],
            ],
            'delivery_timeline' => '1–2 weeks',
            'valid_days' => 30,
            'currency' => 'USD',
            'is_active' => true,
        ], $overrides));
    }

    public function test_admin_creates_a_template_storing_minor_units(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.quotation-templates.store'), [
                'name' => 'Market Entry Sprint',
                'title' => 'Market Entry Sprint',
                'scope_items' => ['Kickoff', ''],
                'line_items' => [
                    ['description' => 'Sprint', 'quantity' => 1, 'unit_amount' => '3500.50'],
                    ['description' => 'Report', 'quantity' => 2, 'unit_amount' => '100'],
                ],
                'valid_days' => 21,
                'delivery_timeline' => '3 weeks',
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.quotation-templates'));

        $template = QuotationTemplate::firstOrFail();

        // Major → minor, no float drift; blank scope dropped.
        $this->assertSame(350050, (int) $template->line_items[0]['unit_amount']);
        $this->assertSame(['Kickoff'], $template->scope_items);
        // Total = 3500.50 + 2 × 100 = 3700.50.
        $this->assertSame(370050, $template->totalMinor());
    }

    public function test_template_requires_name_title_and_line_items(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.quotation-templates.store'), [
                'name' => '',
                'title' => '',
                'line_items' => [],
                'valid_days' => 30,
                'is_active' => true,
            ])
            ->assertSessionHasErrors(['name', 'title', 'line_items']);

        $this->assertSame(0, QuotationTemplate::count());
    }

    public function test_list_page_renders_templates(): void
    {
        $this->template();

        $this->actingAs($this->admin)
            ->get(route('admin.quotation-templates'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/quotation-templates/index')
                ->has('templates', 1)
                ->where('templates.0.total', '$2,000.00')
            );
    }

    public function test_client_less_quotation_link_from_template_is_prefilled(): void
    {
        $template = $this->template();

        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.quotation'), ['template' => $template->uuid])
            ->assertRedirect();

        $link = ConsultationRequest::firstOrFail();

        // No client details — the buyer fills them via the /quote page.
        $this->assertNull($link->name);
        $this->assertNull($link->email);
        // Not a CRM lead — a standalone payment link.
        $this->assertNull($link->lead_stage);
        // Seeded from the template: quotation issued, total from line items.
        $this->assertTrue($link->isQuotation());
        $this->assertSame(200000, (int) $link->amount);
        $this->assertSame(['Discovery Session', 'Growth Analysis'], $link->scope_items);
        $this->assertSame('Strategic Business Assessment', $link->title);

        // The public quotation page renders for the client to fill + pay.
        $this->get(route('consultation.quote', $link))->assertOk();
    }

    public function test_blank_quotation_link_creates_an_unpriced_request(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.payment-links.quotation'), [])
            ->assertRedirect();

        $link = ConsultationRequest::firstOrFail();

        // No template → nothing issued yet; admin completes it on the builder.
        $this->assertFalse($link->isQuotation());
        $this->assertSame(0, (int) $link->amount);
        $this->assertSame(ConsultationStatus::Pending, $link->status);
    }

    public function test_non_admin_cannot_create_a_template(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->post(route('admin.quotation-templates.store'), [
                'name' => 'X', 'title' => 'X',
                'line_items' => [['description' => 'A', 'quantity' => 1, 'unit_amount' => '10']],
                'valid_days' => 30, 'is_active' => true,
            ])
            ->assertForbidden();

        $this->assertSame(0, QuotationTemplate::count());
    }
}
