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
        Schema::create('pdf_convert', function (Blueprint $table) {
            $table->uuid('processId');
            $table->text('fileName');
            $table->string('fileSize', 25);
            $table->string('container', 25);
            $table->boolean('img_extract');
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
        Schema::dropIfExists('pdf_convert');
    }
};
