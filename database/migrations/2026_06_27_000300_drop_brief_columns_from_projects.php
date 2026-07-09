<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['objectives', 'scope', 'start_date', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->text('objectives')->nullable();
            $table->text('scope')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
        });
    }
};
