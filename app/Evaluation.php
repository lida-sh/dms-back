<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    public function architectureEvaluateds(){
        return $this->belongsToMany(ArchitectureEvaluated::class);
    }
    public function architectureScores(){
        return $this->hasMany(ArchitectureScore::class, "evaluation_id");
    }
}
