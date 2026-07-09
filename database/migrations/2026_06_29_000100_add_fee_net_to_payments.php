<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Gateway processing fee and the net the platform actually receives,
            // in minor units (same currency as `amount`). Nullable: historical
            // rows and any gateway that doesn't report a breakdown stay null.
            $table->unsignedBigInteger('fee')->nullable()->after('amount');
            $table->unsignedBigInteger('net')->nullable()->after('fee');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['fee', 'net']);
        });
    }
};
