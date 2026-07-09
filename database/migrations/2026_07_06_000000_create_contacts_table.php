<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('company')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('phone')->nullable();
            // Linked to the login-capable client account only once it is
            // provisioned (on payment) — a contact is a prospect, not a login.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->foreignId('contact_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // Backfill a contact per distinct email from existing consultations/leads,
        // link every matching consultation to it, and attach any existing user.
        $emails = DB::table('consultation_requests')
            ->whereNotNull('email')
            ->distinct()
            ->pluck('email');

        foreach ($emails as $email) {
            $latest = DB::table('consultation_requests')
                ->where('email', $email)
                ->orderByDesc('id')
                ->first();

            $contactId = DB::table('contacts')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => $latest->name ?: 'Contact',
                'email' => $email,
                'company' => $latest->company ?? null,
                'country' => $latest->country ?? null,
                'phone' => $latest->phone ?? null,
                'user_id' => DB::table('users')->where('email', $email)->value('id'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('consultation_requests')->where('email', $email)->update(['contact_id' => $contactId]);
        }
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_id');
        });

        Schema::dropIfExists('contacts');
    }
};
