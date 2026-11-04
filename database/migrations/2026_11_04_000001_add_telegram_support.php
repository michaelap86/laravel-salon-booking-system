<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_id')->nullable()->unique();
        });
        Schema::table('appointments', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('telegram_id'));
        Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('reminded_at'));
    }
};
