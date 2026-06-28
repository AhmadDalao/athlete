<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->after('phone_verified_at')->index();
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('stripe_price_id')->nullable()->after('currency')->unique();
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->string('billing_provider')->nullable()->after('currency');
            $table->string('provider_customer_id')->nullable()->after('billing_provider')->index();
            $table->string('provider_subscription_id')->nullable()->after('provider_customer_id')->unique();
            $table->string('provider_price_id')->nullable()->after('provider_subscription_id')->index();
            $table->json('provider_payload')->nullable()->after('provider_price_id');
        });

        Schema::create('billing_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('provider_event_id');
            $table->string('event_type');
            $table->boolean('livemode')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
            $table->index(['provider', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhook_events');

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn([
                'billing_provider',
                'provider_customer_id',
                'provider_subscription_id',
                'provider_price_id',
                'provider_payload',
            ]);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropUnique(['stripe_price_id']);
            $table->dropColumn('stripe_price_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('stripe_customer_id');
        });
    }
};
