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
        Schema::create('notifyLogs', function (Blueprint $table) {
            $table->id('notifyId');
            $table->uuid('processId');
            $table->string('notifyName', 25);
            $table->boolean('notifyResult');
            $table->text('notifyMessage')->nullable();
            $table->text('notifyErrStatus')->nullable();
            $table->text('notifyErrMessage')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifyLogs');
    }
};
