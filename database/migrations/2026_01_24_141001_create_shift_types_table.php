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
        Schema::create('shift_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 24)->unique();     // WARD, OPD, CLINIC, NIGHT
            $table->string('name', 64);
            $table->string('category', 24)->default('day'); // day/evening/night (optional)
            $table->decimal('weight', 4, 1)->default(1.0);  // workload scoring
            $table->string('color', 16)->nullable();        // for UI (optional)
            $table->timestamps();

            $table->index(['category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_types');
    }
};
