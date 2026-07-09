<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces per-gateway id columns with a single gateway-agnostic reference, so
 * adding a payment rail never again requires a schema change.
 *   payments.{stripe_session_id, stripe_payment_intent, paypal_order_id} → payments.reference
 *   consultation_requests.{stripe_session_id, paypal_order_id} → checkout_gateway + checkout_reference
 *
 * Guarded with hasColumn() so it is safe on an already-migrated DB and on a
 * fresh build (where some legacy columns never existed).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── payments.reference ────────────────────────────────────────────────
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'reference')) {
                $table->string('reference')->nullable()->after('gateway');
            }
        });

        $sources = array_values(array_filter(
            ['stripe_session_id', 'paypal_order_id'],
            fn ($c) => Schema::hasColumn('payments', $c),
        ));

        if ($sources) {
            $expr = count($sources) > 1 ? 'COALESCE('.implode(', ', $sources).')' : $sources[0];
            DB::table('payments')->whereNull('reference')->update(['reference' => DB::raw($expr)]);
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->unique('reference');

            foreach (['stripe_session_id', 'paypal_order_id'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropUnique([$col]);
                }
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            foreach (['stripe_session_id', 'stripe_payment_intent', 'paypal_order_id'] as $col) {
                if (Schema::hasColumn('payments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        // ── consultation_requests.checkout_gateway / checkout_reference ───────
        Schema::table('consultation_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_requests', 'checkout_gateway')) {
                $table->string('checkout_gateway')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('consultation_requests', 'checkout_reference')) {
                $table->string('checkout_reference')->nullable()->after('checkout_gateway');
            }
        });

        $hasStripe = Schema::hasColumn('consultation_requests', 'stripe_session_id');
        $hasPaypal = Schema::hasColumn('consultation_requests', 'paypal_order_id');

        if ($hasStripe || $hasPaypal) {
            $when = [];
            $refCols = [];

            if ($hasStripe) {
                $when[] = "WHEN stripe_session_id IS NOT NULL THEN 'stripe'";
                $refCols[] = 'stripe_session_id';
            }
            if ($hasPaypal) {
                $when[] = "WHEN paypal_order_id IS NOT NULL THEN 'paypal'";
                $refCols[] = 'paypal_order_id';
            }

            $caseGateway = 'CASE '.implode(' ', $when).' ELSE NULL END';
            $refExpr = count($refCols) > 1 ? 'COALESCE('.implode(', ', $refCols).')' : $refCols[0];

            DB::statement(
                "UPDATE consultation_requests SET checkout_gateway = {$caseGateway}, checkout_reference = {$refExpr} WHERE checkout_reference IS NULL"
            );
        }

        Schema::table('consultation_requests', function (Blueprint $table) {
            foreach (['stripe_session_id', 'paypal_order_id'] as $col) {
                if (Schema::hasColumn('consultation_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable();
            }
            if (! Schema::hasColumn('payments', 'stripe_payment_intent')) {
                $table->string('stripe_payment_intent')->nullable();
            }
            if (Schema::hasColumn('payments', 'reference')) {
                $table->dropUnique(['reference']);
                $table->dropColumn('reference');
            }
        });

        Schema::table('consultation_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('consultation_requests', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable();
            }
            foreach (['checkout_gateway', 'checkout_reference'] as $col) {
                if (Schema::hasColumn('consultation_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
