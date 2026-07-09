<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('specialization')->nullable();
            $table->char('country', 2)->nullable();
            $table->boolean('is_active')->default(true);

            // Payout rail: Stripe Connect (Express). The expert needs no Stripe login.
            $table->string('preferred_payout_method')->default('stripe'); // rail key
            $table->string('stripe_account_id')->nullable();              // acct_...
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->text('payout_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experts');
    }
};
