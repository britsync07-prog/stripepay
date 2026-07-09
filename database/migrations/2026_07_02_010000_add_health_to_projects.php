<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('health')->nullable()->after('completion_percentage');
            $table->text('health_summary')->nullable()->after('health');
            $table->json('health_flags')->nullable()->after('health_summary');
            $table->timestamp('health_checked_at')->nullable()->after('health_flags');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['health', 'health_summary', 'health_flags', 'health_checked_at']);
        });
    }
};
