<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // DB-level idempotency: a Stripe session can be recorded at most once.
            // (Nullable column — MySQL permits multiple NULLs, so non-Stripe rows are fine.)
            $table->unique('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['stripe_session_id']);
        });
    }
};
