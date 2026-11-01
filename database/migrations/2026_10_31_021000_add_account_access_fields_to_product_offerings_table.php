<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offerings', function (Blueprint $table) {
            $table->string('email')->nullable()->after('account_type_id');
            $table->timestamp('starts_at')->nullable()->after('duration_days');
            $table->timestamp('expires_at')->nullable()->after('starts_at');
            $table->text('api_key')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_offerings', function (Blueprint $table) {
            $table->dropColumn(['email', 'starts_at', 'expires_at', 'api_key']);
        });
    }
};
