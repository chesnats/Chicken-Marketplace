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
        if (! Schema::hasColumn('listings', 'chicken_type')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->string('chicken_type')->nullable()->after('user_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('listings', 'chicken_type')) {
            Schema::table('listings', function (Blueprint $table) {
                $table->dropColumn('chicken_type');
            });
        }
    }
};
