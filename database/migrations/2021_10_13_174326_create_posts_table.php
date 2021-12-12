<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->bigIncrements('post_id');
            $table->longText('text');
            $table->string('username', 20);
            $table->string('classcode', 7);
            $table->boolean('is_delete')->default(false);
            $table->foreign('username')->references('username')->on('user_access');
            $table->foreign('classcode')->references('classcode')->on('user_access');
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
        Schema::dropIfExists('posts');
    }
}
