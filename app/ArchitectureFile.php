<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArchitectureFile extends Model
{
    protected $table = "architecture_files";
    protected $guarded = [];

    public function process(){
        return $this->belongsTo(Architecture::class, "architecture_id");
    }
    public function getFilePathAttribute($attr){
        return public_path(env('DIR_UPLOAD')). $attr;
    }
}
