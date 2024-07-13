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
        Schema::create('accessLogs', function (Blueprint $table) {
            $table->id('accessId');
            $table->uuid('processId');
            $table->text('routePath')->nullable();
            $table->text('accessIpAddress')->nullable();
            $table->timestamp('createdAt')->nullable()->useCurrent()->useCurrentOnUpdate();

            // Configure foreign key
            $table->foreign('processId')->references('processId')->on('appLogs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accessLogs');
    }
};
