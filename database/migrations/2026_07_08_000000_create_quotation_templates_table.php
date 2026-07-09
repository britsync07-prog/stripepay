<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable quotation presets. An admin composes a scope + line items once and
 * reuses it to spin up a quotation link in one click, without re-typing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_templates', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');                          // internal label
            $table->string('title');                         // buyer-facing offer title
            $table->json('scope_items')->nullable();
            $table->json('line_items')->nullable();          // unit_amount in minor units
            $table->string('delivery_timeline')->nullable();
            $table->unsignedSmallInteger('valid_days')->default(30);
            $table->string('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_templates');
    }
};
