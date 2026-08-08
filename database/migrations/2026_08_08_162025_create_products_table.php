<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('telegram_groups')->cascadeOnDelete();
            $table->string('product_key', 500);
            $table->text('product_name');
            $table->decimal('latest_price', 15, 2)->nullable();
            $table->string('latest_photo_url')->nullable();
            $table->bigInteger('latest_message_id')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->unique(['group_id', 'product_key'], 'products_group_key_unique');
            $table->index('last_updated_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
