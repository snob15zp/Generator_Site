<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique()->nullable(false);
            $table->string('password');
            $table->string('salt');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
            $table->foreign('role_id')->references('id')->on('user_role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user', function (Blueprint $table){
            $table->dropForeign('user_role_id');
        });
        Schema::dropIfExists('user');
    }
}
