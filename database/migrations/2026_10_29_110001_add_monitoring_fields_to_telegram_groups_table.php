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
            $table->boolean('is_active')->default(true)->after('photo_url')->index();
            $table->string('sync_status', 30)->default('idle')->after('is_active')->index();
            $table->text('last_error')->nullable()->after('sync_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('telegram_groups', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['sync_status']);
            $table->dropColumn([
                'is_active',
                'sync_status',
                'last_error',
            ]);
        });
    }
};

