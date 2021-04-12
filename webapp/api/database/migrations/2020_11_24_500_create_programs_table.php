<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('folder_id');
            $table->string('name');
            $table->string('hash')->nullable(false);
            $table->boolean('is_encrypted');
            $table->boolean('active');
            $table->timestamps();
            $table->foreign('folder_id')->references('id')->on('folder');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program', function (Blueprint $table){
            $table->dropForeign('folder_id');
        });
        Schema::dropIfExists('program');
    }
}
