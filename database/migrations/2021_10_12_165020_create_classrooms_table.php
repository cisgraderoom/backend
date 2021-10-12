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
            $table->string('classcode', 7)->unique();
            $table->string('classname', 50);
            $table->string('teacher_id', 13);
            $table->integer('section')->nullable();
            $table->enum('semester', [1, 2, 3])->nullable();
            $table->year('year')->nullable()->default(date('Y'));
            $table->boolean('is_open')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->boolean('is_ban')->default(false);
            $table->foreign('teacher_id')
                ->references('username')
                ->on('users')
                ->cascadeOnDelete();
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
