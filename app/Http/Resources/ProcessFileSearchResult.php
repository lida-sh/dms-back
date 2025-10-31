<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProcessFileSearchResult extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'file_name' => $this->file_name ?? null,
            'file_path' => $this->file_path ?? null,
            'process_name' => $this->process_name ?? null,
            'found_in_text' => $this->found_in_text ?? [],
            'found_in_images' => $this->found_in_images ?? [],
            'status' => $this->status ?? 'unknown',
        ];
    }
}
