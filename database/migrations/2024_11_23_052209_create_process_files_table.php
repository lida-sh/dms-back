<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcessFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('process_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("process_id");
            $table->foreign("process_id")->references("id")->on("processes")->onDelete("cascade");
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
        Schema::dropIfExists('process_files');
    }
}
