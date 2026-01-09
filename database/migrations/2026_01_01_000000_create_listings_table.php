<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('breed');
            $table->text('description');
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('age_weeks');
            $table->string('location');
            $table->enum('status', ['active', 'sold', 'flagged'])->default('active');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('listings'); }
};