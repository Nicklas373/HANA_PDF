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
        Schema::create('extract_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('fileName');
            $table->string('fileSize');
            $table->string('hostName');
            $table->string('customPage')->nullable();
            $table->string('mergePDF')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extract_pdfs');
    }
};
