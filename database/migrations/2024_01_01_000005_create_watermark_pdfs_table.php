<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection that should be used by the migration.
     *
     * @var string
     */
    protected $connection = 'pgsql';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pdfWatermark', function (Blueprint $table) {
            $table->id('watermarkId');
            $table->text('fileName')->nullable();
            $table->string('fileSize', 25)->nullable();
            $table->string('watermarkFontFamily', 25)->nullable();
            $table->string('watermarkFontStyle', 25)->nullable();
            $table->string('watermarkFontSize', 5)->nullable();
            $table->string('watermarkFontTransparency', 5)->nullable();
            $table->text('watermarkImage')->nullable();
            $table->string('watermarkLayout', 25)->nullable();
            $table->string('watermarkMosaic', 25)->nullable();
            $table->string('watermarkRotation', 25)->nullable();
            $table->string('watermarkStyle', 25)->nullable();
            $table->text('watermarkText')->nullable();
            $table->string('watermarkPage', 25)->nullable();
            $table->boolean('result');
            $table->boolean('isBatch');
            $table->uuid('processId');
            $table->uuid('batchId')->nullable();
            $table->timestamp('procStartAt')->nullable();
            $table->timestamp('procEndAt')->nullable();
            $table->text('procDuration')->nullable();
            $table->boolean('isReport')->nullable()->default(false);

            // Configure foreign key
            $table->foreign('processId')->references('processId')->on('appLogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdfWatermark');
    }
};
