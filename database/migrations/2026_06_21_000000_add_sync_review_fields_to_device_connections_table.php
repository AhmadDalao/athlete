<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_connections', function (Blueprint $table): void {
            $table->timestamp('last_sync_started_at')->nullable()->after('last_synced_at');
            $table->timestamp('last_error_at')->nullable()->after('last_sync_started_at');
            $table->text('last_error_message')->nullable()->after('last_error_at');
            $table->unsignedInteger('sync_failures_count')->default(0)->after('last_error_message');
        });
    }

    public function down(): void
    {
        Schema::table('device_connections', function (Blueprint $table): void {
            $table->dropColumn([
                'last_sync_started_at',
                'last_error_at',
                'last_error_message',
                'sync_failures_count',
            ]);
        });
    }
};
