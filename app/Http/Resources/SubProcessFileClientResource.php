<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubProcessFileClientResource extends JsonResource
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
            "fileName" => $this->fileName,
            "filePath" => url("/storage/files/sub-processes")."/".$this->filePath,
        ];
    }
}
