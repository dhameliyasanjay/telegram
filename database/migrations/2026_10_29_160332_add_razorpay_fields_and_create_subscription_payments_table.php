<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add Razorpay tracking fields to subscription_orders
        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->string('razorpay_order_id')->nullable()->after('status')->index();
            $table->string('razorpay_payment_id')->nullable()->after('razorpay_order_id')->index();
            $table->string('razorpay_signature')->nullable()->after('razorpay_payment_id');
            $table->timestamp('paid_at')->nullable()->after('activated_at');
        });

        // 2. Create subscription_payments table for granular audit history, retry handling, and webhook idempotency
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('subscription_order_id')->constrained('subscription_orders')->cascadeOnDelete();
            $table->string('razorpay_order_id')->index();
            $table->string('razorpay_payment_id')->nullable()->unique();
            $table->string('razorpay_signature')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');
            $table->string('status', 30)->default('created')->index(); // created, authorized, captured, failed, refunded
            $table->string('payment_method')->nullable(); // upi, card, netbanking, wallet
            $table->string('error_code')->nullable();
            $table->text('error_description')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['subscription_order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');

        Schema::table('subscription_orders', function (Blueprint $table) {
            $table->dropIndex(['razorpay_order_id']);
            $table->dropIndex(['razorpay_payment_id']);
            $table->dropColumn([
                'razorpay_order_id',
                'razorpay_payment_id',
                'razorpay_signature',
                'paid_at',
            ]);
        });
    }
};
