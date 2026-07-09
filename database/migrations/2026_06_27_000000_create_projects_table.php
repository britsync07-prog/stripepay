<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference')->nullable()->unique();

            // An engagement becomes a project. The link is nullable so admins
            // can also spin up a standalone project not tied to a booking.
            $table->foreignId('consultation_request_id')->nullable()->constrained()->nullOnDelete();
            // Denormalised client + assigned consultant for direct scoping.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expert_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('objectives')->nullable();
            $table->text('scope')->nullable();

            $table->string('status')->default('planning');
            $table->unsignedTinyInteger('completion_percentage')->default(0);

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
