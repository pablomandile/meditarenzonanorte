<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('date_text')->nullable();
            $table->date('starts_at')->nullable();
            $table->string('location')->nullable();
            $table->string('price')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->string('image_path', 500)->nullable();
            $table->boolean('visible')->default(true);
            $table->boolean('show_on_home')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
