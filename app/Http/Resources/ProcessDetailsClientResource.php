<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcessDetailsClientResource extends JsonResource
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
            "description" => $this->description,
            "files" => ProcessFileClientResource::collection($this->whenLoaded("files")),
            "architecture" => new ArchitectureClientResource($this->whenLoaded("architecture"))
        ];
    }
}
