<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest-submitted consultation requests. No authentication required —
        // the public UUID is the only identifier exposed in URLs.
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            // Collected by Stripe Checkout during payment (guest pays first, no form).
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->char('country', 2)->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('amount')->default(0); // minor units, snapshot of plan price
            $table->char('currency', 3)->default('USD');
            $table->string('status')->default('pending');      // pending | paid | cancelled
            $table->string('stripe_session_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
