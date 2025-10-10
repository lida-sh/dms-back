<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArchitectureFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('architecture_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("architecture_id");
            $table->foreign("architecture_id")->references("id")->on("architectures")->onDelete("cascade");
            $table->string("fileName");
            $table->string("filePath");
            $table->tinyInteger("status")->default(1);
            $table->softDeletes();
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
        Schema::dropIfExists('architecture_files');
    }
}
