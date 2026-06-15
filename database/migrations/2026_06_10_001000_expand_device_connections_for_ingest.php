<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_connections', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->after('id');
            $table->text('ingest_key')->nullable()->after('external_user_id');
            $table->string('ingest_key_last_four', 4)->nullable()->after('ingest_key');
        });

        DB::table('device_connections')
            ->select(['id'])
            ->orderBy('id')
            ->get()
            ->each(function (object $connection): void {
                $plainKey = 'thl_'.Str::lower(Str::random(32));

                DB::table('device_connections')
                    ->where('id', $connection->id)
                    ->update([
                        'public_id' => (string) Str::uuid(),
                        'ingest_key' => Crypt::encryptString($plainKey),
                        'ingest_key_last_four' => substr($plainKey, -4),
                    ]);
            });

        Schema::table('device_connections', function (Blueprint $table) {
            $table->unique('public_id');
            $table->index(['provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('device_connections', function (Blueprint $table) {
            $table->dropIndex(['provider', 'status']);
            $table->dropUnique(['public_id']);
            $table->dropColumn(['public_id', 'ingest_key', 'ingest_key_last_four']);
        });
    }
};
