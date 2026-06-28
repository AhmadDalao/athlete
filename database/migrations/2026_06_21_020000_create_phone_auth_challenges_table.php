<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_auth_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('intent', 20);
            $table->string('phone', 25);
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('account_type', 20)->nullable();
            $table->string('preferred_contact_method', 20)->nullable();
            $table->string('delivery_driver', 40);
            $table->string('code_hash');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('primary_goal')->nullable();
            $table->timestamps();

            $table->index(['intent', 'phone']);
            $table->index(['expires_at', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_auth_challenges');
    }
};
