<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlagiarismTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('plagiarism', function (Blueprint $table) {
            $table->string('owner', 20);
            $table->string('compare', 20);
            $table->unsignedBigInteger('problem_id');
            $table->float('result', 5, 2);
            $table->primary(['owner', 'compare', 'problem_id']);
            $table->foreign('owner')->references('username')->on('users');
            $table->foreign('compare')->references('username')->on('users');
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
        Schema::dropIfExists('plagiarism');
    }
}
