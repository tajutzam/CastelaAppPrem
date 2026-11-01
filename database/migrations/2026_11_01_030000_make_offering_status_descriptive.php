<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->string('status_new')->default('active')->after('status');
        });

        DB::table('product_offerings')->update(['status_new' => DB::raw("CASE WHEN status = true THEN 'active' ELSE 'deactivated' END")]);

        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->dropColumn('status');
            $table->renameColumn('status_new', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->boolean('status_new')->default(true)->after('status');
        });

        DB::table('product_offerings')->update(['status_new' => DB::raw("status = 'active'")]);

        Schema::table('product_offerings', function (Blueprint $table): void {
            $table->dropColumn('status');
            $table->renameColumn('status_new', 'status');
        });
    }
};
