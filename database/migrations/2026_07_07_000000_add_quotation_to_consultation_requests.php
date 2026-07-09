<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the quotation layer onto consultation requests. A quotation is a priced
 * custom request that also carries a formal document number, an issue date, a
 * bulleted scope of services and itemised line items. The existing `amount` /
 * `currency` hold the total and `expires_at` doubles as the validity date.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->string('quote_number')->nullable()->unique()->after('reference');
            $table->date('quote_issued_at')->nullable()->after('quote_number');
            $table->json('scope_items')->nullable()->after('message');
            $table->json('line_items')->nullable()->after('scope_items');
            $table->string('delivery_timeline')->nullable()->after('line_items');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn([
                'quote_number',
                'quote_issued_at',
                'scope_items',
                'line_items',
                'delivery_timeline',
            ]);
        });
    }
};
