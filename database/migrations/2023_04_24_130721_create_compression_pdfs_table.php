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
        Schema::create('compression_pdfs', function (Blueprint $table) {
            $table->id();
            $table->string('fileName');
            $table->string('fileSize');
            $table->string('compMethod');
            $table->string('hostName');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compression_pdfs');
    }
};
