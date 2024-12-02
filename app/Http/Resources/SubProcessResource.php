<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubProcessResource extends JsonResource
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
            "status" => $this->status,
            "architecture_id" => $this->architecture_id,
            "process_id" => $this->process_id,
            "description" => $this->description,
            "files" => SubProcessFileResource::collection($this->whenLoaded("files")),
            "architecture" => new ArchitectureResource($this->whenLoaded("architecture")),
            "process" => new ProcessResource($this->whenLoaded("process"))
        ];
    }
}
