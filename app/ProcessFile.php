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
    public function scopeWithAllowedExtensions($query, $extensions = ['pdf', 'jpeg', 'png', 'jpg'])
    {
        return $query->where(function ($query) use ($extensions) {
            foreach ($extensions as $extension) {
                $query->orWhereRaw("LOWER(SUBSTRING_INDEX(filePath, '.', -1)) = ?", [$extension]);
            }
        });
    }
}
