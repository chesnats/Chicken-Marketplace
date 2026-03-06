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
        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1)->after('price');
            }

            if (! Schema::hasColumn('listings', 'weight_kg')) {
                $table->decimal('weight_kg', 8, 2)->nullable()->after('quantity');
            }

            if (! Schema::hasColumn('listings', 'size_label')) {
                $table->string('size_label')->nullable()->after('weight_kg');
            }

            if (! Schema::hasColumn('listings', 'chicken_condition')) {
                $table->string('chicken_condition')->default('live')->after('size_label');
            }

            if (! Schema::hasColumn('listings', 'delivery_option')) {
                $table->string('delivery_option')->default('pickup_only')->after('chicken_condition');
            }

            if (! Schema::hasColumn('listings', 'contact_preference')) {
                $table->string('contact_preference')->default('platform_message')->after('delivery_option');
            }

            if (! Schema::hasColumn('listings', 'category_tags')) {
                $table->json('category_tags')->nullable()->after('contact_preference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            foreach ([
                'category_tags',
                'contact_preference',
                'delivery_option',
                'chicken_condition',
                'size_label',
                'weight_kg',
                'quantity',
            ] as $column) {
                if (Schema::hasColumn('listings', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
