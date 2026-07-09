<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount'); // minor units (brick/money)
            $table->char('currency', 3)->default('USD');
            $table->string('method')->default('manual');  // manual | stripe | binance
            $table->string('status')->default('pending');  // pending | processing | paid | failed
            $table->string('reference')->nullable();        // stripe transfer id / binance id / manual ref
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('expert_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
