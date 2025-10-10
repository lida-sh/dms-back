<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
class Process extends Model
{
    use Sluggable;
    protected $table = "processes";
    protected $guarded = [];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title'
            ]
        ];
    }
    public function files(){
        return $this->hasMany(ProcessFile::class, "process_id");
    }
    public function architecture(){
        return $this->belongsTo(Architecture::class, "architecture_id");
    }
    public function subProcesses(){
        return $this->hasMany(SubProcess::class, "process_id");
    }
    public function user(){
        return $this->belongsTo(User::class, "user_id");
    }
}
