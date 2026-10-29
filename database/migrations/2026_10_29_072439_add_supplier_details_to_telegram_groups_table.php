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
        Schema::table('telegram_groups', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('invite_link');
            $table->string('location')->nullable()->after('phone');
            $table->text('address')->nullable()->after('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_groups', function (Blueprint $table) {
            $table->dropColumn(['phone', 'location', 'address']);
        });
    }
};
