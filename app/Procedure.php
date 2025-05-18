<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
class Procedure extends Model
{
    use Sluggable;
    protected $table = "procedures";
    protected $guarded = [];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
    public function process(){
        return $this->belongsTo(Process::class, "process_id");
    }
    public function architecture(){
        return $this->belongsTo(Architecture::class, "architecture_id");
    }
    public function files(){
        return $this->hasMany(ProcedureFile::class, "procedure_id");
    }
     public function user(){
        return $this->belongsTo(User::class, "user_id");
    }
}
