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
        Schema::create('muhurats', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('type');
            $table->string('titleLink');
            $table->string('formatted_date');
            $table->string('message')->nullable();
            $table->string('image')->nullable();
            $table->string('muhurat')->nullable();
            $table->string('nakshatra')->nullable();
            $table->string('tithi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('muhurats');
    }
};
