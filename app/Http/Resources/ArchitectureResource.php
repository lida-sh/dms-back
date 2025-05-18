<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArchitectureResource extends JsonResource
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
            "id" => $this->id,
            "title" => $this->title,
            "code" => $this->code,
            "slug" => $this->slug,
            "type" => $this->type,
            "description" => $this->description,
            "files" => ArchitectureFileResource::collection($this->whenLoaded("files")),
            "user" => new userBaseResource($this->whenLoaded("user"))
        ];
    }
}
