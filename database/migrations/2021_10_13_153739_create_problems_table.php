<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CreateProblemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('problems', function (Blueprint $table) {
            $table->bigIncrements('problem_id')->autoIncrement();
            $table->string('problem_name');
            $table->string('problem_desc')->nullable();
            $table->dateTime('open_at')->default(now());
            $table->dateTime('close_at')->nullable();
            $table->boolean('is_hidden')->default(false);
            $table->boolean('is_delete')->default(false);
            $table->integer('testcase');
            $table->double('max_score', 5, 2);
            $table->string('username', 20);
            $table->string('classcode', 7);
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
        Schema::dropIfExists('problems');
    }
}
