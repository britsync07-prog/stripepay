<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The dashboard read-model runs many aggregates filtered by paid status
        // and paid_at window (revenue series, composition, gateway breakdown…).
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'paid_at']);
        });
    }
};
