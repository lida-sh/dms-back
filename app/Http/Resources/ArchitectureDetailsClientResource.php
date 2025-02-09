<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArchitectureDetailsClientResource extends JsonResource
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
            "title" => $this->title,
            "code" => $this->code,
            "type" => $this->type,
            "description" => $this->description,
            "files" => ArchitectureFileClientResource::collection($this->whenLoaded("files")),
            "processes"=> ProcessClientResource::collection($this->whenLoaded("processes"))
        ];
    }
}
