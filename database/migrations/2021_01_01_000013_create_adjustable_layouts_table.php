<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdjustableLayoutsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('cortex.foundation.tables.adjustable_layouts'), function (Blueprint $table) {
            // Columns
            $table->increments('id');
            $table->string('element_id');
            $table->json('data')->nullable();
            $table->auditableAndTimestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('cortex.foundation.tables.adjustable_layouts'));
    }
}
