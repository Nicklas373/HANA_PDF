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
        Schema::create('watermark_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('fileName');
            $table->string('fileSize');
            $table->string('hostName');
            $table->string('watermarkFontFamily')->nullable();
            $table->string('watermarkFontStyle')->nullable();
            $table->string('watermarkFontSize')->nullable();
            $table->string('watermarkFontTransparency')->nullable();
            $table->string('watermarkImage')->nullable();
            $table->string('watermarkLayout')->nullable();
            $table->string('watermarkMosaic')->nullable();
            $table->string('watermarkRotation')->nullable();
            $table->string('watermarkStyle')->nullable();
            $table->string('watermarkText')->nullable();
            $table->string('watermarkPage')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watermark_pdfs');
    }
};
