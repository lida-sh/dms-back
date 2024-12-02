<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProcedureFile extends Model
{
    protected $table = "procedure_files";
    protected $guarded = [];

    public function procedure(){
        return $this->belongsTo(Procedure::class, "procedure_id");
    }
}
