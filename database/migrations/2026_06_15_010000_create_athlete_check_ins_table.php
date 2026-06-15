<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('logged_date');
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('body_fat_percentage', 5, 2)->nullable();
            $table->decimal('waist_cm', 6, 2)->nullable();
            $table->unsignedInteger('calories_consumed')->nullable();
            $table->unsignedInteger('protein_grams')->nullable();
            $table->unsignedInteger('carbs_grams')->nullable();
            $table->unsignedInteger('fat_grams')->nullable();
            $table->decimal('water_liters', 4, 1)->nullable();
            $table->unsignedTinyInteger('meals_logged_count')->nullable();
            $table->unsignedTinyInteger('energy_score')->nullable();
            $table->unsignedTinyInteger('soreness_score')->nullable();
            $table->unsignedTinyInteger('stress_score')->nullable();
            $table->unsignedTinyInteger('sleep_quality_score')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'logged_date']);
            $table->index(['logged_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_check_ins');
    }
};
