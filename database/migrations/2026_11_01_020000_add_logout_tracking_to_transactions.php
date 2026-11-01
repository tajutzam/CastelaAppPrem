<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->timestamp('logged_out_at')->nullable()->after('paid_at');
            $table->foreignId('logged_out_by')->nullable()->after('logged_out_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropForeign(['logged_out_by']);
            $table->dropColumn(['logged_out_at', 'logged_out_by']);
        });
    }
};
