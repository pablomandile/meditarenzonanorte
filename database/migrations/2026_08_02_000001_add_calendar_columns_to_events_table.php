<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->date('ends_at')->nullable()->after('starts_at');
            $table->time('start_time')->nullable()->after('ends_at');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('show_on_calendar')->default(false)->after('show_on_home');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['ends_at', 'start_time', 'end_time', 'show_on_calendar']);
        });
    }
};
