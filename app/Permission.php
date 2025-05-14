<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;

class Permission extends Model
{
    use HasRoles;
    protected $table = "permissions";
    protected $guarded = [];
    // public function role(){
    //     return $this->belongsTo(Role::class);
    // }

}
