<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            // Admin-authored custom offers carry their own title (there is no
            // plan to borrow a name from). plan_id IS NULL signals "custom".
            $table->string('title')->nullable()->after('plan_id');

            // Optional pay-by date for custom payment links (negotiated prices
            // should not linger). Plan-based checkouts leave this null.
            $table->timestamp('expires_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropColumn(['title', 'expires_at']);
        });
    }
};
