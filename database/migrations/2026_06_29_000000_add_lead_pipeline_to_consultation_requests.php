<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->string('lead_stage')->nullable()->index()->after('work_status');
            $table->string('lead_source')->nullable()->after('lead_stage');
            $table->timestamp('next_follow_up_at')->nullable()->after('lead_source');
            $table->text('lost_reason')->nullable()->after('next_follow_up_at');
        });

        // Backfill existing custom-plan requests (plan_id null) into the pipeline,
        // deriving each one's stage from its current state. Plan-based purchases are
        // not leads and stay out of the pipeline (lead_stage = null).
        DB::table('consultation_requests')->whereNull('plan_id')->where('status', 'paid')
            ->update(['lead_stage' => 'won', 'lead_source' => 'form']);
        DB::table('consultation_requests')->whereNull('plan_id')->where('status', 'cancelled')
            ->update(['lead_stage' => 'lost', 'lead_source' => 'form']);
        DB::table('consultation_requests')->whereNull('plan_id')->whereNull('lead_stage')
            ->where('amount', '>', 0)->update(['lead_stage' => 'quoted', 'lead_source' => 'form']);
        DB::table('consultation_requests')->whereNull('plan_id')->whereNull('lead_stage')
            ->update(['lead_stage' => 'new', 'lead_source' => 'form']);
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn(['lead_stage', 'lead_source', 'next_follow_up_at', 'lost_reason']);
        });
    }
};
