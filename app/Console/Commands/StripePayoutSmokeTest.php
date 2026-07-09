<?php

namespace App\Console\Commands;

use App\Models\Expert;
use App\Payments\PayoutRailManager;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Stripe\StripeClient;
use Throwable;

/**
 * End-to-end payout smoke test for the Stripe Connect rail (TEST MODE ONLY).
 *
 * Creates a fully-onboarded test connected account via the Connect API, funds
 * the platform balance with an instantly-available test charge, then drives the
 * real app payout path (PayoutRailManager -> StripeRail::pay -> StripeConnect::
 * transfer) and asserts the money moved. Also proves idempotency (same key does
 * not double-pay). Cleans up the account + expert row on the way out.
 *
 * Custom accounts require the platform-profile "responsibilities" setting at
 * dashboard.stripe.com/settings/connect/platform-profile to be accepted once.
 */
#[Signature('stripe:payout-smoke-test')]
#[Description('Run an end-to-end Stripe Connect payout against a throwaway test account (test mode only).')]
class StripePayoutSmokeTest extends Command
{
    public function handle(PayoutRailManager $rails): int
    {
        $secret = (string) config('services.stripe.secret');

        if (! str_contains($secret, 'sk_test')) {
            $this->error('Refusing to run: STRIPE_SECRET is not a test key (sk_test_…). This command moves real money on live keys.');

            return self::FAILURE;
        }

        // The Stripe PHP SDK raises a non-fatal E_USER_WARNING deprecation notice
        // AFTER the call already succeeded; Laravel would escalate it to a fatal
        // ErrorException and orphan the new account. Swallow it for this command.
        set_error_handler(static fn (): bool => true, E_USER_WARNING);

        $stripe = new StripeClient($secret);
        $expert = null;
        $accountId = null;

        try {
            $this->line('<fg=cyan>STEP A</> create a fully-onboarded test connected account');
            $account = $stripe->accounts->create([
                'type' => 'custom',
                'country' => 'US',
                'email' => 'payout-smoke@example.com',
                'capabilities' => ['transfers' => ['requested' => true]],
                'business_type' => 'individual',
                'individual' => [
                    'first_name' => 'Jenny',
                    'last_name' => 'Rosen',
                    'email' => 'payout-smoke@example.com',
                    'phone' => '+15555551234',
                    'ssn_last_4' => '0000',
                    'id_number' => '000000000',
                    'dob' => ['day' => 1, 'month' => 1, 'year' => 1990],
                    'address' => [
                        'line1' => 'address_full_match',
                        'city' => 'South San Francisco',
                        'state' => 'CA',
                        'postal_code' => '94080',
                        'country' => 'US',
                    ],
                ],
                'business_profile' => ['mcc' => '5734', 'url' => 'https://blackdesck.com'],
                'external_account' => [
                    'object' => 'bank_account',
                    'country' => 'US',
                    'currency' => 'usd',
                    'routing_number' => '110000000',
                    'account_number' => '000123456789',
                ],
                'tos_acceptance' => ['date' => time(), 'ip' => '8.8.8.8'],
            ]);
            $accountId = $account->id;
            $account = $stripe->accounts->retrieve($accountId);
            $transfersCap = $account->capabilities->transfers ?? 'n/a';
            $this->line("       account {$accountId} — transfers capability: <fg=green>{$transfersCap}</>");

            if ($transfersCap !== 'active') {
                $this->warn('       transfers capability is not active yet; the transfer below will be rejected by Stripe.');
            }

            $this->line('<fg=cyan>STEP B</> fund the platform balance (instantly-available test charge)');
            $stripe->charges->create([
                'amount' => 5000,
                'currency' => 'usd',
                'source' => 'tok_bypassPending',
                'description' => 'payout smoke-test top-up',
            ]);
            $available = collect($stripe->balance->retrieve()->available)->firstWhere('currency', 'usd');
            $this->line('       available USD balance: <fg=green>'.($available['amount'] ?? 0).'</> minor units');

            $this->line('<fg=cyan>STEP C</> run the app payout (PayoutRailManager → StripeRail::pay)');
            $expert = Expert::create([
                'name' => 'Payout Smoke Test',
                'email' => 'payout-smoke-expert@example.com',
                'specialization' => 'QA',
                'country' => 'US',
                'is_active' => true,
                'preferred_payout_method' => 'stripe',
                'stripe_account_id' => $accountId,
                'stripe_payouts_enabled' => true,
            ]);

            $rail = $rails->rail($rails->getDefaultDriver());
            $idempotencyKey = 'smoke-'.$expert->id;
            $result = $rail->pay($expert, 2500, 'USD', $idempotencyKey, 'Consultation payout (smoke test)');
            $transfer = $stripe->transfers->retrieve($result->reference);
            $this->line("       PayoutResult: <fg=green>{$result->status}</> — transfer {$transfer->id} "
                .'moved '.$transfer->amount.' '.strtoupper($transfer->currency)." → {$transfer->destination}");

            $this->line('<fg=cyan>STEP D</> idempotency — same key must not double-pay');
            $again = $rail->pay($expert, 2500, 'USD', $idempotencyKey, 'Consultation payout (smoke test)');
            $same = $again->reference === $result->reference;
            $this->line('       second call reference '.($same ? '<fg=green>matched</> ✔ (no double-pay)' : '<fg=red>DIFFERED</> ✘ (double-paid!)'));

            if ($result->status !== 'paid' || ! $same) {
                $this->newLine();
                $this->error('SMOKE TEST FAILED — see steps above.');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('*** FULL PAYOUT FLOW PASSED — money moved end-to-end ***');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->newLine();
            $this->error('SMOKE TEST FAILED: '.$e->getMessage());

            if (str_contains($e->getMessage(), 'platform-profile')) {
                $this->warn('Accept requirement-collection responsibility once at:');
                $this->warn('  https://dashboard.stripe.com/settings/connect/platform-profile');
            }

            return self::FAILURE;
        } finally {
            if ($expert) {
                $expert->delete();
            }
            if ($accountId) {
                try {
                    $stripe->accounts->delete($accountId);
                } catch (Throwable) {
                    // best-effort cleanup
                }
            }
            restore_error_handler();
            $this->line('<fg=gray>cleanup: removed throwaway expert + connected account</>');
        }
    }
}
