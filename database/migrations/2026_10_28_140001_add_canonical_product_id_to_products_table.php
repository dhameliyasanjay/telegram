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
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('canonical_product_id')
                ->nullable()
                ->after('id')
                ->constrained('canonical_products')
                ->nullOnDelete();

            $table->index('canonical_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['canonical_product_id']);
            $table->dropColumn('canonical_product_id');
        });
    }
};

