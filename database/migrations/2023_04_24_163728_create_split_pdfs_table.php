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
        Schema::create('pdf_split', function (Blueprint $table) {
            $table->id();
            $table->string('fileName');
            $table->string('fileSize');
            $table->string('fromPage')->nullable();
            $table->string('toPage')->nullable();
            $table->string('customPage')->nullable();
            $table->string('fixedPage')->nullable();
            $table->string('fixedPageRange')->nullable();
            $table->string('mergePDF')->nullable();
            $table->boolean('result');
            $table->string('err_reason')->nullable();
            $table->string('err_api_reason')->nullable();
            $table->string('uuid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_split');
    }
};
