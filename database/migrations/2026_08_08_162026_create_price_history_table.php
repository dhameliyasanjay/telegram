<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('price', 15, 2)->nullable();
            $table->string('photo_url')->nullable();
            $table->bigInteger('message_id')->nullable();
            $table->text('message_text')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index('posted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
