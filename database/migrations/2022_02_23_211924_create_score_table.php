<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('score', function (Blueprint $table) {
            $table->primary(['username', 'classcode', 'problem_id']);
            $table->string('username', 20);
            $table->string('classcode', 7);
            $table->unsignedBigInteger('problem_id');
            $table->float('score', 5, 2)->default(0);
            $table->foreign('username')->references('username')->on('user_access');
            $table->foreign('classcode')->references('classcode')->on('user_access');
            $table->foreign('problem_id')->references('problem_id')->on('problems');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('score');
    }
}
