<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_products', function (Blueprint $table) {
            $table->id();
            $table->string('photo_path')->nullable();
            $table->text('product_name');
            $table->string('sku_id')->unique();
            $table->decimal('bank_settlement', 15, 2)->nullable();
            $table->decimal('market_price', 15, 2)->nullable();
            $table->integer('qty')->nullable();
            $table->string('group_name')->nullable();
            $table->enum('status', ['pending', 'on-hold', 'active'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_products');
    }
};
