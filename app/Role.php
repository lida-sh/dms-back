<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Traits\HasRoles;
class Role extends Model
{
    use HasRoles;
    protected $table = "roles";
    protected $guarded = [];
    // public function permissions(){
    //     return $this->permissions();
    // }

}
