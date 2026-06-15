<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_connections', function (Blueprint $table) {
            $table->string('auth_type')->default('ingest_key')->after('status');
            $table->text('access_token')->nullable()->after('ingest_key_last_four');
            $table->text('refresh_token')->nullable()->after('access_token');
            $table->timestamp('token_expires_at')->nullable()->after('refresh_token');
            $table->json('provider_account_payload')->nullable()->after('granted_scopes');

            $table->index(['provider', 'auth_type']);
        });
    }

    public function down(): void
    {
        Schema::table('device_connections', function (Blueprint $table) {
            $table->dropIndex(['provider', 'auth_type']);
            $table->dropColumn([
                'auth_type',
                'access_token',
                'refresh_token',
                'token_expires_at',
                'provider_account_payload',
            ]);
        });
    }
};
