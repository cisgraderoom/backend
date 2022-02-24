<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubmissionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('submission', function (Blueprint $table) {
            $table->bigIncrements('submission_id');
            $table->string('username', 20);
            $table->string('classcode', 7);
            $table->unsignedBigInteger('problem_id');
            $table->text('code')->nullable();
            $table->string('result')->nullable();
            $table->float('score', 5, 2)->default(0);
            $table->string('lang');
            $table->timestamp('created_at');
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
        Schema::dropIfExists('submission');
    }
}
