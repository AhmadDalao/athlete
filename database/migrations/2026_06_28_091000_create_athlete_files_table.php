<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('athlete_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('athlete_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category')->default('admin');
            $table->string('visibility')->default('coach_admin');
            $table->string('status')->default('active');
            $table->string('display_name');
            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['athlete_id', 'status']);
            $table->index(['category', 'status']);
            $table->index(['visibility', 'status']);
            $table->index(['uploaded_by_user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('athlete_files');
    }
};
