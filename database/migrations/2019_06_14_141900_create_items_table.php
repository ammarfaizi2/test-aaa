<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('checklist_id');
            $table->string('name');
            $table->dateTime('due')->nullable();
            $table->integer('urgency')->nullable();;
            $table->unsignedBigInteger('assignee_id')->nullable();;
            $table->string('task_id')->nullable();;
            $table->dateTime('completed_at')->nullable();
            $table->unsignedBigInteger('last_update_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('items');
    }
}
