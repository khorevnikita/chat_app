<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBaseTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('spaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string("subdomain");
            $table->string("name")->nullable();
            $table->boolean("is_active")->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('space_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('space_id');
            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string("rights")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('channels', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('space_id');
            $table->foreign('space_id')->references('id')->on('spaces')->onDelete('cascade');
            $table->string("name")->nullable();
            $table->string("access")->nullable();
            $table->string("type")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('channel_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string("rights")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('messages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('channel_id');
            $table->foreign('channel_id')->references('id')->on('channels')->onDelete('cascade');
            $table->text("value")->nullable();
            $table->string("type")->nullable();
            $table->boolean("pinned")->nullable();
            $table->boolean("need_report")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('message_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('message_id');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->boolean("is_author")->default(0);
            $table->boolean("read")->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_user');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('channel_user');
        Schema::dropIfExists('channels');
        Schema::dropIfExists('space_user');
        Schema::dropIfExists('spaces');
    }
}
