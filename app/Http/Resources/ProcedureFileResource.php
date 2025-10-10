<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProcedureFileResource extends JsonResource
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
            "fileName" => $this->fileName,
            "status" => $this->status,
            "filePath" => url("/storage/files/procedures")."/".$this->filePath,
        ];
    }
}
