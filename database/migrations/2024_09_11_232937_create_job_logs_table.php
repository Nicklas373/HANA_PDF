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
        Schema::create('jobLogs', function (Blueprint $table) {
            $table->id('jobsId')->primary()->unique();
            $table->char('jobsName', length: 25);
            $table->enum('jobsEnv', ['production', 'local']);
            $table->char('jobsRuntime', length: 25);
            $table->boolean('jobsResult');
            $table->uuid('groupId');
            $table->uuid('processId')->unique();
            $table->timestamp('procStartAt')->nullable();
            $table->timestamp('procEndAt')->nullable();
            $table->char('procDuration', length: 25)->nullable();
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
        Schema::dropIfExists('jobLogs');
    }
};
