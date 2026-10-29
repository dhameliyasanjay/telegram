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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('password')->index();
            $table->string('status', 20)->default('active')->after('role')->index();
            $table->timestamp('trial_ends_at')->nullable()->after('status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('subscription_status', 30)->default('trial')->after('subscription_ends_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropIndex(['subscription_status']);
            $table->dropColumn([
                'role',
                'status',
                'trial_ends_at',
                'subscription_ends_at',
                'subscription_status',
            ]);
        });
    }
};

