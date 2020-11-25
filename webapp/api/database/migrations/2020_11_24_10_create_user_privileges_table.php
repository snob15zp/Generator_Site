<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPrivilegesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_privileges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("user_role_id");
            $table->enum('name', ['manage_profile', 'view_profile', 'create_user', 'manage_programs', 'view_programs']);
            $table->unique(['user_role_id', 'name']);
            $table->foreign('user_role_id')->references('id')->on('user_role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_privileges', function (Blueprint $table){
            $table->dropForeign('user_role_id');
        });
        Schema::dropIfExists('user_privileges');
    }
}
