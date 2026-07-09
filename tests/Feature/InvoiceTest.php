<?php

namespace Tests\Feature;

use App\Actions\Admin\CreateInvoiceFromConsultation;
use App\Actions\Payments\FlagPayment;
use App\Enums\InvoiceStatus;
use App\Models\ConsultationRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('admin');
        Role::findOrCreate('client');
    }

    private function paidConsultation(User $client, int $amount = 250000): ConsultationRequest
    {
        return ConsultationRequest::create([
            'user_id' => $client->id,
            'title' => 'International Business Expansion',
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    public function test_paying_a_consultation_issues_one_paid_invoice(): void
    {
        $client = User::factory()->create();
        $consultation = $this->paidConsultation($client);

        $invoice = CreateInvoiceFromConsultation::run($consultation);
        CreateInvoiceFromConsultation::run($consultation->fresh()); // idempotent

        $this->assertSame(1, Invoice::where('consultation_request_id', $consultation->id)->count());
        $this->assertSame(InvoiceStatus::Paid, $invoice->status);
        $this->assertSame(250000, (int) $invoice->total);
        $this->assertSame(250000, (int) $invoice->subtotal);
        $this->assertSame(0, (int) $invoice->tax_amount);
        $this->assertSame(1, $invoice->items()->count());
        $this->assertSame($client->id, $invoice->user_id);
        $this->assertStringStartsWith('INV-', $invoice->reference);
    }

    public function test_tax_is_broken_out_inclusively_and_total_equals_the_charged_amount(): void
    {
        $client = User::factory()->create();
        $consultation = $this->paidConsultation($client, 250000);
        $invoice = CreateInvoiceFromConsultation::run($consultation);

        // A prepaid receipt: tax is inclusive — broken out *of* the amount charged,
        // never added on top. The total must always equal what the client paid.
        $invoice->update(['tax_rate' => 15]);
        $invoice->recalculateTotals();
        $invoice->refresh();

        $this->assertSame(217391, (int) $invoice->subtotal); // 250000 / 1.15
        $this->assertSame(32609, (int) $invoice->tax_amount); // total - subtotal
        $this->assertSame('15.00', (string) $invoice->tax_rate);
        $this->assertSame(250000, (int) $invoice->total);
        $this->assertSame((int) $consultation->amount, (int) $invoice->total);
    }

    public function test_an_unpriced_consultation_does_not_get_an_invoice(): void
    {
        $client = User::factory()->create();
        $consultation = $this->paidConsultation($client, amount: 0);

        $this->assertNull(CreateInvoiceFromConsultation::run($consultation));
        $this->assertSame(0, Invoice::count());
    }

    public function test_a_refund_marks_the_invoice_refunded(): void
    {
        Notification::fake();

        $client = User::factory()->create();
        $consultation = $this->paidConsultation($client);
        $invoice = CreateInvoiceFromConsultation::run($consultation);

        $payment = Payment::create([
            'consultation_request_id' => $consultation->id,
            'gateway' => 'stripe',
            'reference' => 'ref_test_1',
            'amount' => 250000,
            'currency' => 'USD',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        FlagPayment::run($payment, 'refunded');

        $this->assertSame(InvoiceStatus::Refunded, $invoice->fresh()->status);
    }

    public function test_a_client_cannot_download_another_clients_invoice(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('client');
        $intruder = User::factory()->create();
        $intruder->assignRole('client');

        $invoice = CreateInvoiceFromConsultation::run($this->paidConsultation($owner));

        // Intruder is blocked.
        $this->actingAs($intruder)
            ->get(route('portal.invoice.pdf', $invoice))
            ->assertForbidden();

        // Owner can download their own.
        $this->actingAs($owner)
            ->get(route('portal.invoice.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_an_admin_can_download_any_invoice(): void
    {
        $client = User::factory()->create();
        $client->assignRole('client');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $invoice = CreateInvoiceFromConsultation::run($this->paidConsultation($client));

        $this->actingAs($admin)
            ->get(route('admin.invoices.pdf', $invoice))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
