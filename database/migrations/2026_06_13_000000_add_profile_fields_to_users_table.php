<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email')->unique();
            $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            $table->string('primary_goal')->nullable()->after('password');
            $table->string('preferred_contact_method')->default('email')->after('primary_goal');
            $table->string('registration_channel')->default('email')->after('preferred_contact_method');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'phone_verified_at',
                'primary_goal',
                'preferred_contact_method',
                'registration_channel',
            ]);
        });
    }
};
