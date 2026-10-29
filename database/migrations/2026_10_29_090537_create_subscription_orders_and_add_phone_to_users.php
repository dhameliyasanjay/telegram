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
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 25)->nullable()->after('email');
            });
        }

        Schema::create('subscription_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 25);
            $table->string('customer_email');
            $table->string('customer_city')->nullable();
            $table->decimal('amount', 10, 2)->default(49.00);
            $table->unsignedInteger('plan_duration_days')->default(30);
            $table->string('payment_method')->default('UPI / WhatsApp');
            $table->string('status', 20)->default('pending'); // pending, active, rejected
            $table->text('notes')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_orders');

        if (Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('phone');
            });
        }
    }
};
