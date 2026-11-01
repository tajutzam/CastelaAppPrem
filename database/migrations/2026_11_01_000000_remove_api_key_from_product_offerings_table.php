<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->dropColumn('api_key');
        });
    }

    public function down(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->text('api_key')->nullable()->after('expires_at');
        });
    }
};
