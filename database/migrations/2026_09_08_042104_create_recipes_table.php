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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('rating', 2, 1)->nullable();
            $table->text('ingredients')->nullable();   // planning on doing markdown on frontend
            $table->text('instructions')->nullable();  // planning on doing markdown on frontend
            $table->string('source_url')->nullable();
            $table->unsignedSmallInteger('total_minutes')->nullable();
            $table->unsignedTinyInteger('servings')->nullable();
            $table->boolean('make_again')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
