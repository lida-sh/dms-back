<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchitectureScoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('architecture_scores', function (Blueprint $table) {
            $table->id();
            $table->float('score');
            $table->unsignedBigInteger("evaluation_id");
            $table->unsignedBigInteger("architecture_evaluated_id");
            $table->foreign('architecture_evaluated_id')->references('id')->on('architecture_evaluateds')->onDelete('cascade');
            $table->foreign('evaluation_id')->references('id')->on('evaluations')->onDelete('cascade');
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
        Schema::dropIfExists('architecture_scores');
    }
}
