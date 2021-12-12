<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->bigIncrements('comment_id');
            $table->foreignId('post_id')->references('post_id')->on('posts');
            $table->string('username', 20);
            $table->string('classcode', 7);
            $table->longText('comment_content');
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
        Schema::dropIfExists('comments');
    }
}
