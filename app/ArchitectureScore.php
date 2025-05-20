<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ArchitectureScore extends Model
{
    public function evaluation(){
        return $this->belongsTo(Evaluation::class, "evaluation_id");
    }
    public function architectureEvaluated(){
        return $this->belongsTo(Evaluation::class, "architecture_evaluated_id");
    }
}
