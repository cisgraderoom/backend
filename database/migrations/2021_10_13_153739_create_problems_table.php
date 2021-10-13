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
            $table->string('problem_id')->unique()->default(Str::random(8))->primary();
            $table->string('problem_name');
            $table->string('problem_description')->nullable();
            $table->enum('type', ['auto', 'manual'])->default('manual');
            $table->dateTime('open_at')->default(now());
            $table->dateTime('close_at')->nullable();
            $table->boolean('hidden')->default(false);
            $table->string('username', 13);
            $table->string('classcode', 7);
            $table->foreign('username')->references('username')->on('users');
            $table->foreign('classcode')->references('classcode')->on('classrooms');
            $table->boolean('is_delete')->default(false);
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
