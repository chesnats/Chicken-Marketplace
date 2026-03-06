<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'seller_default_contact_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('seller_default_contact_preference');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'seller_default_contact_preference')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('seller_default_contact_preference')->nullable()->after('seller_default_location');
            });
        }
    }
};
