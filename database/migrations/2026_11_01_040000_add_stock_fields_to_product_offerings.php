<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->text('notes')->nullable()->after('email');
            $table->dropForeign(['product_id']);
            $table->dropForeign(['account_type_id']);
            $table->dropUnique('product_offerings_product_id_account_type_id_unique');
            $table->unique(['product_id', 'account_type_id', 'email']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('account_type_id')->references('id')->on('account_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['account_type_id']);
            $table->dropUnique('product_offerings_product_id_account_type_id_email_unique');
            $table->unique(['product_id', 'account_type_id']);
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreign('account_type_id')->references('id')->on('account_types')->restrictOnDelete();
            $table->dropColumn('notes');
        });
    }
};
