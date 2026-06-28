<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type')->default('all');
            $table->string('target_role')->nullable();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('action_label')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_role']);
            $table->index(['starts_at', 'expires_at']);
        });

        Schema::create('system_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['system_notification_id', 'user_id'], 'notification_user_read_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_notification_reads');
        Schema::dropIfExists('system_notifications');
    }
};
