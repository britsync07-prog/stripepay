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
            $table->string('reference')->nullable()->unique()->after('uuid');
            $table->foreignId('expert_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('consultation_type_id')->nullable()->after('expert_id')->constrained()->nullOnDelete();
            $table->string('urgency')->nullable()->after('message');
            $table->unsignedBigInteger('budget_min')->nullable()->after('urgency');    // minor units (brick/money)
            $table->unsignedBigInteger('budget_max')->nullable()->after('budget_min');  // minor units (brick/money)
            $table->char('budget_currency', 3)->nullable()->after('budget_max');
            $table->timestamp('assigned_at')->nullable()->after('paid_at');
            $table->timestamp('resolved_at')->nullable()->after('assigned_at');
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
        });

        // Backfill a human reference for existing rows: CD-{year}-{padded id}.
        foreach (DB::table('consultation_requests')->whereNull('reference')->get(['id', 'created_at']) as $row) {
            $year = $row->created_at ? date('Y', strtotime((string) $row->created_at)) : date('Y');
            DB::table('consultation_requests')
                ->where('id', $row->id)
                ->update(['reference' => sprintf('CD-%s-%06d', $year, $row->id)]);
        }
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expert_id');
            $table->dropConstrainedForeignId('consultation_type_id');
            $table->dropColumn([
                'reference',
                'urgency',
                'budget_min',
                'budget_max',
                'budget_currency',
                'assigned_at',
                'resolved_at',
                'closed_at',
            ]);
        });
    }
};
