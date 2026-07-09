<?php

namespace Tests\Feature;

use App\Actions\Admin\CreateInvoiceFromConsultation;
use App\Models\ConsultationRequest;
use App\Models\User;
use App\Services\SettingsService;
use App\Settings\PlatformSettings;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CurrencySeeder::class);
    }

    private function admin(): User
    {
        Role::findOrCreate('admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_defaults_resolve_to_usd_and_zero_tax(): void
    {
        $settings = app(PlatformSettings::class);

        $this->assertSame('USD', $settings->currency_code);
        $this->assertSame(0.0, $settings->tax_rate);
        $this->assertSame('USD', SettingsService::currencyCode());
        $this->assertSame('$', SettingsService::currencySymbol());
    }

    public function test_admin_can_view_the_settings_page(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('admin/settings')
                    ->where('settings.currency_code', 'USD')
                    ->has('settings.tax_rate')
                    ->has('settings.company_name')
            );
    }

    public function test_admin_can_update_settings(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'currency_code' => 'AED',
                'tax_rate' => 5,
                'company_name' => 'BlackDesck Advisory',
                'company_email' => 'billing@example.com',
                'vat_number' => 'VAT-123',
            ])
            ->assertRedirect();

        $settings = app(PlatformSettings::class);
        $this->assertSame('AED', $settings->currency_code);
        $this->assertSame('د.إ', $settings->currency_symbol); // derived from the table
        $this->assertSame(5.0, $settings->tax_rate);
        $this->assertSame('BlackDesck Advisory', $settings->company_name);
    }

    public function test_an_unknown_currency_code_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->patch(route('admin.settings.update'), [
                'currency_code' => 'ZZZ',
                'currency_symbol' => 'Z',
                'tax_rate' => 0,
                'company_name' => 'Acme',
            ])
            ->assertSessionHasErrors('currency_code');

        // The stored value is untouched — money formatting stays safe.
        $this->assertSame('USD', app(PlatformSettings::class)->currency_code);
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        Role::findOrCreate('client');
        $client = User::factory()->create();
        $client->assignRole('client');

        $this->actingAs($client)
            ->patch(route('admin.settings.update'), ['currency_code' => 'EUR'])
            ->assertForbidden();
    }

    public function test_new_invoice_snapshots_tax_rate_and_breaks_it_out_inclusively(): void
    {
        $settings = app(PlatformSettings::class);
        $settings->tax_rate = 10;
        $settings->save();

        $client = User::factory()->create();
        $consultation = ConsultationRequest::create([
            'user_id' => $client->id,
            'amount' => 110000,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $invoice = CreateInvoiceFromConsultation::run($consultation);

        $this->assertSame('10.00', (string) $invoice->tax_rate);
        $this->assertSame(110000, (int) $invoice->total); // unchanged = charged
        $this->assertSame(100000, (int) $invoice->subtotal); // 110000 / 1.10
        $this->assertSame(10000, (int) $invoice->tax_amount);
    }
}
