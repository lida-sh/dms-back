<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcessClientResource;
use App\Http\Resources\ProcessDetailsClientResource;
use App\Process;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\Admin\ApiController;
class ProcessClientController extends ApiController
{
    public function index(Request $request){
        $search = $request->input("search");
        $architecture_id = $request->input("architecture_id");
        $sortedBy = $request->input("sortedBy");
        $procedures = Process::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
        })->when($search, function ($query, $search) {
            return $query->where('title', 'LIKE', "%{$search}%");
        })->when($sortedBy, function ($query, $sortedBy) {
            if ($sortedBy == "newest") {
                return $query->latest();
            } else if ($sortedBy == "oldest")
                return $query->oldest();
        })->where("status", 1)->paginate(10);
        return $this->successResponse([
            "processes" => ProcessClientResource::collection($procedures->load(["architecture"])),
            "links" => ProcessClientResource::collection($procedures)->response()->getData()->links,
            "meta" => ProcessClientResource::collection($procedures)->response()->getData()->meta
        ], 200);
    }
    public function showBySlug($slug){
        $processes = Process::where('slug', $slug)->first();
        return $this->successResponse((new ProcessDetailsClientResource($processes->load(["files" => function ($query) {
            $query->withAllowedExtensions(['pdf', 'jpeg', 'png']); // فیلتر فایل‌ها بر اساس پسوند
        }, "architecture"]))), 200);
    }
}
