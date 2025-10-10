<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubProcessFile extends Model
{
    protected $table = "sub_process_files";
    protected $guarded = [];

    public function subProcess(){
        return $this->belongsTo(SubProcess::class, "sub_process_id");
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
