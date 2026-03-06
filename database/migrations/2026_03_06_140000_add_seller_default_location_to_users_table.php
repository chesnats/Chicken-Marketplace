<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'seller_default_location')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('seller_default_location')->nullable()->after('role');
            });
        }

        DB::table('users')
            ->where('role', 'seller')
            ->whereNull('seller_default_location')
            ->update([
                'seller_default_location' => 'Bulacao Sta. Filomena Alegria, Cebu',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'seller_default_location')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('seller_default_location');
            });
        }
    }
};
