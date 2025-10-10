<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubProcessFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_process_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("sub_process_id");
            $table->foreign("sub_process_id")->references("id")->on("sub_processes")->onDelete("cascade");
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
        Schema::dropIfExists('sub_process_files');
    }
}
