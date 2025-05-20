<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArchitectureEvaluated extends Model
{
    public function evaluation(){
        return $this->belongsToMany(Evaluation::class);
    }
}
