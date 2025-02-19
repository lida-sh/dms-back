<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Hekmatinasser\Verta\Verta;
class ProcedureDetailsClientResource extends JsonResource
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
            "docType" => $this->docType,
            "description" => $this->description,
            "notification_date" => $this->notification_date ? Verta::instance($this->notification_date)->format('Y/m/d'): "",
            "files" => ProcedureFileClientResource::collection($this->whenLoaded("files")),
            "architecture" => new ArchitectureClientResource($this->whenLoaded("architecture")),
            "process" => new ProcessClientResource($this->whenLoaded("process"))
        ];
    }
}
