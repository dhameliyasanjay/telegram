<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert existing single group_name strings to JSON arrays
        DB::table('catalogue_products')->get()->each(function ($row) {
            $current = $row->group_name;
            if ($current && !str_starts_with($current, '[')) {
                DB::table('catalogue_products')
                    ->where('id', $row->id)
                    ->update(['group_name' => json_encode([$current])]);
            }
        });

        Schema::table('catalogue_products', function (Blueprint $table) {
            $table->text('group_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('catalogue_products', function (Blueprint $table) {
            $table->string('group_name')->nullable()->change();
        });
    }
};
