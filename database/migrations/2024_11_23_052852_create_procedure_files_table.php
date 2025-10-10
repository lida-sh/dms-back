<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProcedureFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('procedure_files', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger("procedure_id");
            $table->foreign("procedure_id")->references("id")->on("procedures")->onDelete("cascade");
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
        Schema::dropIfExists('procedure_files');
    }
}
