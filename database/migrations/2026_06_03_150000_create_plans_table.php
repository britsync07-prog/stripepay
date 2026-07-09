<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price')->default(0);   // minor units (brick/money)
            $table->char('currency', 3)->default('USD');
            $table->string('billing_interval')->default('per_engagement'); // BillingInterval enum
            $table->json('features')->nullable();               // string[]
            $table->string('outcome')->nullable();              // "Expected Outcome"
            $table->string('cta_label')->default('Pay');
            $table->boolean('is_featured')->default(false);     // "Most Chosen"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
