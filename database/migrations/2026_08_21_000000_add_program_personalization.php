<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_programs', function (Blueprint $table): void {
            $table->foreignId('source_program_id')
                ->nullable()
                ->after('athlete_id')
                ->constrained('training_programs', indexName: 'training_programs_source_fk')
                ->nullOnDelete();
            $table->index(['coach_id', 'is_template', 'status'], 'training_programs_coach_kind_status_ix');
        });

        Schema::table('program_assignments', function (Blueprint $table): void {
            $table->timestamp('published_at')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('program_assignments', function (Blueprint $table): void {
            $table->dropColumn('published_at');
        });

        Schema::table('training_programs', function (Blueprint $table): void {
            $table->dropIndex('training_programs_coach_kind_status_ix');
            $table->dropForeign('training_programs_source_fk');
            $table->dropColumn('source_program_id');
        });
    }
};
