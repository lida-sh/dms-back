<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Hekmatinasser\Verta\Verta;
class ProcedureResource extends JsonResource
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
            "docType" => $this->docType,
            "architecture_id" => $this->architecture_id,
            "process_id" => $this->process_id,
            "description" => $this->description,
            "notification_date" =>  $this->notification_date ? Verta::instance($this->notification_date)->format('Y/m/d'): "",
            "files" => ProcedureFileResource::collection($this->whenLoaded("files")),
            "architecture" => new ArchitectureResource($this->whenLoaded("architecture")),
            "process" => new ProcessResource($this->whenLoaded("process")),
            "user" => new userBaseResource($this->whenLoaded("user"))
        ];
    }
}
