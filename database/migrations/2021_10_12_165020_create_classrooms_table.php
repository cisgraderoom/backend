<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClassroomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->string('classcode', 7)->unique()->primary();
            $table->string('classname', 50);
            $table->string('teacher', 20);
            $table->tinyInteger('section')->nullable();
            $table->tinyInteger('term')->nullable();
            $table->integer('year')->nullable()->default(date('Y'));
            $table->boolean('is_open')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->foreign('teacher')
                ->references('username')
                ->on('users')
                ->restrictOnDelete();
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
        Schema::dropIfExists('classrooms');
    }
}
