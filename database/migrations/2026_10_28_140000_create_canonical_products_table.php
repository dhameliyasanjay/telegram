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
        Schema::create('canonical_products', function (Blueprint $table) {
            $table->id();
            $table->string('canonical_key', 255)->unique();
            $table->string('name', 255);
            $table->string('brand', 100)->nullable()->index();
            $table->string('model', 100)->nullable()->index();
            $table->string('category', 100)->nullable()->index();
            $table->string('primary_photo_url', 500)->nullable();
            $table->string('photo_hash', 64)->nullable()->index();
            $table->decimal('cheapest_price', 15, 2)->nullable()->index();
            $table->foreignId('cheapest_group_id')->nullable()->constrained('telegram_groups')->nullOnDelete();
            $table->decimal('highest_price', 15, 2)->nullable();
            $table->unsignedInteger('offers_count')->default(0)->index();
            $table->timestamp('last_observed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canonical_products');
    }
};

