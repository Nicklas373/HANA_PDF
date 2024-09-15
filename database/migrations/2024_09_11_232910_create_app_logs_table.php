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
        Schema::create('appLogs', function (Blueprint $table) {
            $table->uuid('processId');
            $table->text('errReason')->nullable();
            $table->text('errStatus')->nullable();
            $table->timestamp('createdAt')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Configure primary key
            $table->primary('processId');

            // Configure foreign keys
            $table->foreign('processId')->references('processId')->on('pdfCompress')->name('pdf_compress_fk');
            $table->foreign('processId')->references('processId')->on('pdfMerge')->name('pdf_merge_fk');
            $table->foreign('processId')->references('processId')->on('pdfSplit')->name('pdf_split_fk');
            $table->foreign('processId')->references('processId')->on('pdfDelete')->name('pdf_delete_fk');
            $table->foreign('processId')->references('processId')->on('pdfCnv')->name('pdf_cnv_fk');
            $table->foreign('processId')->references('processId')->on('pdfWatermark')->name('pdf_watermark_fk');
            $table->foreign('processId')->references('processId')->on('pdfHtml')->name('pdf_html_fk');
            $table->foreign('processId')->references('processId')->on('jobLogs')->name('job_logs_fk');
            $table->foreign('processId')->references('processId')->on('notifyLogs')->name('notify_logs_fk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appLogs');
    }
};
