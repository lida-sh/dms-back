<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubProcessClientResource;
use App\Http\Resources\SubProcessDetailsClientResource;
use App\SubProcess;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\Admin\ApiController;
class SubProcessClientController extends ApiController
{
    public function index(Request $request){
        $search = $request->input("search");
        $architecture_id = $request->input("architecture_id");
        $process_id = $request->input("process_id");
        $sortedBy = $request->input("sortedBy");
        $subProcesses = SubProcess::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
        })->when($process_id, function ($query, $process_id) {
            return $query->where("process_id", $process_id);
        })->when($search, function ($query, $search) {
            return $query->where('title', 'LIKE', "%{$search}%");
        })->when($sortedBy, function ($query, $sortedBy) {
            if ($sortedBy == "newest") {
                return $query->latest();
            } else if ($sortedBy == "oldest")
                return $query->oldest();
        })->where("status", 1)->paginate(10);
        return $this->successResponse([
            "subProcesses" => SubProcessClientResource::collection($subProcesses->load(["architecture", "process"])),
            "links" => SubProcessClientResource::collection($subProcesses)->response()->getData()->links,
            "meta" => SubProcessClientResource::collection($subProcesses)->response()->getData()->meta
        ], 200);
    }
    public function showBySlug($slug){
        $subProcesses = SubProcess::where('slug', $slug)->first();
        return $this->successResponse((new SubProcessDetailsClientResource($subProcesses->load(["files" => function ($query) {
            $query->withAllowedExtensions(['pdf', 'jpeg', 'png']); // فیلتر فایل‌ها بر اساس پسوند
        }, "architecture", "process"]))), 200);
    }
}
