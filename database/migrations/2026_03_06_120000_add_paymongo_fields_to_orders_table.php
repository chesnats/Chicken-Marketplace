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
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'payment_status')) {
                $table->string('payment_status')->default('pending')->after('payment_method');
            }

            if (! Schema::hasColumn('orders', 'paymongo_checkout_session_id')) {
                $table->string('paymongo_checkout_session_id')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('orders', 'paymongo_payment_reference')) {
                $table->string('paymongo_payment_reference')->nullable()->after('paymongo_checkout_session_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'paymongo_payment_reference')) {
                $table->dropColumn('paymongo_payment_reference');
            }

            if (Schema::hasColumn('orders', 'paymongo_checkout_session_id')) {
                $table->dropColumn('paymongo_checkout_session_id');
            }

            if (Schema::hasColumn('orders', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
