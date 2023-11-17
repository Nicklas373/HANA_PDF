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
        Schema::create('pdf_split', function (Blueprint $table) {
           $table->uuid('processId');
            $table->text('fileName');
            $table->string('fileSize', 25);
            $table->string('fromPage', 5)->nullable();
            $table->string('toPage', 5)->nullable();
            $table->text('customPage')->nullable();
            $table->text('fixedPage')->nullable();
            $table->text('fixedPageRange')->nullable();
            $table->string('mergePDF', 25)->nullable();
            $table->boolean('result');
            $table->text('err_reason')->nullable();
            $table->text('err_api_reason')->nullable();
            $table->timestamp('procStartAt')->nullable();

            $table->primary('processId');
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
