<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'seller_contact_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('seller_contact_number', 20)->nullable()->after('seller_default_contact_preference');
            });
        }

        DB::table('users')
            ->where('role', 'seller')
            ->whereNull('seller_contact_number')
            ->update([
                'seller_contact_number' => '09491735243',
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'seller_contact_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('seller_contact_number');
            });
        }
    }
};
