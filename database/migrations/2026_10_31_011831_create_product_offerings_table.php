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
        Schema::create('product_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_type_id')->constrained()->restrictOnDelete();
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('duration_days');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'account_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_offerings');
    }
};
