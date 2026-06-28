<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whoop_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trace_id')->unique();
            $table->string('whoop_user_id');
            $table->string('resource_id');
            $table->string('event_type');
            $table->string('processing_status')->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error_message')->nullable();
            $table->timestamp('received_at');
            $table->timestamp('processed_at')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['processing_status', 'received_at']);
            $table->index(['whoop_user_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whoop_webhook_events');
    }
};
