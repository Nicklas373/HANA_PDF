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
        Schema::create('pdfHtml', function (Blueprint $table) {
            $table->id('htmlId');
            $table->text('urlName')->nullable();
            $table->text('urlMargin')->nullable();
            $table->text('urlOrientation')->nullable();
            $table->boolean('urlSinglePage')->nullable();
            $table->text('urlSize')->nullable();
            $table->boolean('result');
            $table->uuid('processId');
            $table->timestamp('procStartAt')->nullable();
            $table->timestamp('procEndAt')->nullable();
            $table->text('procDuration')->nullable();
            $table->boolean('isReport')->nullable()->default(false);
            $table->timestamp('createdAt')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updatedAt')->nullable()->useCurrentOnUpdate();

            // Configure foreign key
            $table->foreign('processId')->references('processId')->on('appLogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdfHtml');
    }
};
