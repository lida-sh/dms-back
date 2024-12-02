<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProcessFile extends Model
{
    protected $table = "process_files";
    protected $guarded = [];

    public function process(){
        return $this->belongsTo(Process::class, "process_id");
    }
}
