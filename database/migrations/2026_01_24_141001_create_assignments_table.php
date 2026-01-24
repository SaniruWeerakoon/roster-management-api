<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->foreignId('shift_type_id')->constrained('shift_types')->cascadeOnDelete();
            $table->timestamps();

            // Prevent duplicates of same person+date+shift within the roster
            $table->unique(['roster_id', 'date', 'person_id', 'shift_type_id'], 'uniq_assignment');

            // Speed up common queries
            $table->index(['roster_id', 'date']);
            $table->index(['roster_id', 'person_id']);
            $table->index(['roster_id', 'shift_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
