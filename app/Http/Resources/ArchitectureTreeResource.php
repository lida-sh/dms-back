<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ArchitectureTreeResource extends JsonResource
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
            "processes"=> ProcessTreeResource::collection($this->whenLoaded("processes", function(){
                return $this->processes->load("subProcesses");
            }))
        ];
    }
}
