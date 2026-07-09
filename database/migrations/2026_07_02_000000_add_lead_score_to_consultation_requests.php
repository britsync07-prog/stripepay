<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('lead_score')->nullable()->after('lost_reason');
            $table->text('lead_score_summary')->nullable()->after('lead_score');
            $table->json('lead_score_flags')->nullable()->after('lead_score_summary');
            $table->timestamp('lead_scored_at')->nullable()->after('lead_score_flags');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn(['lead_score', 'lead_score_summary', 'lead_score_flags', 'lead_scored_at']);
        });
    }
};
