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
            $table->id('htmlId')->primary()->unique();
            $table->text('urlName')->nullable();
            $table->integer('urlMargin')->nullable();
            $table->enum('urlOrientation', ['landscape','portrait'])->nullable();
            $table->boolean('urlSinglePage')->nullable();
            $table->enum('urlSize', ['A3','A4','A5','Letter'])->nullable();
            $table->boolean('result');
            $table->uuid('groupId');
            $table->uuid('processId')->unique();
            $table->timestamp('procStartAt')->nullable();
            $table->timestamp('procEndAt')->nullable();
            $table->char('procDuration', length: 25)->nullable();
            $table->boolean('isReport')->nullable()->default(false);
            $table->timestamp('created_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

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
