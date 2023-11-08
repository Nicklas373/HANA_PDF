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
        Schema::create('pdf_merge', function (Blueprint $table) {
            $table->id();
            $table->text('fileName');
            $table->string('fileSize');
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
        Schema::dropIfExists('pdf_merge');
    }
};
