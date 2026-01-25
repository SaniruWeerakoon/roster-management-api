<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roster_shift_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('roster_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('required_per_day')->nullable();// Future use (MVP-safe to ignore)
            $table->timestamps();

            $table->unique(['roster_id', 'shift_type_id']);
            $table->index(['roster_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roster_shift_types');
    }
};
