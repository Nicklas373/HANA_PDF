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
        Schema::create('datalogs', function (Blueprint $table) {
            $table->uuid('jobsId');
            $table->string('jobsName', 25);
            $table->string('jobsEnv', 25);
            $table->string('jobsRuntime', 25);
            $table->boolean('jobsResult');
            $table->longText('jobsErrMessage')->nullable();
            $table->timestamp('jobsStart')->nullable();
            $table->timestamp('jobsEnd')->nullable();

            $table->primary('jobsId');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datalogs');
    }
};
