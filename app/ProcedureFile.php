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
    public function scopeWithAllowedExtensions($query, $extensions = ['pdf', 'jpeg', 'png', 'jpg'])
    {
        return $query->where(function ($query) use ($extensions) {
            foreach ($extensions as $extension) {
                $query->orWhereRaw("LOWER(SUBSTRING_INDEX(filePath, '.', -1)) = ?", [$extension]);
            }
        });
    }
}
