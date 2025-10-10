<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            "id"=>$this->id,
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "email" => $this->email,
            "national_code" => $this->national_code,
            "password" => $this->password,
            'roles' => RoleResource::collection($this->whenLoaded("roles")),
            'permissions' => $this->getAllPermissions()->pluck('name'),

        ];
    }
}
