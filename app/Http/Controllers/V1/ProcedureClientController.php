<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcedureClientResource;
use App\Http\Resources\ProcedureDetailsClientResource;
use App\Procedure;
use Illuminate\Http\Request;
use App\Http\Controllers\V1\Admin\ApiController;
class ProcedureClientController extends ApiController
{
    public function index(Request $request){
        $search = $request->input("search");
        $architecture_id = $request->input("architecture_id");
        $process_id = $request->input("process_id");
        $docType = $request->input("docType");
        $sortedBy = $request->input("sortedBy");
        $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
        })->when($process_id, function ($query, $process_id) {
            return $query->where("process_id", $process_id);
        })->when($docType, function ($query, $docType) {
            return $query->where("docType", $docType);
        })->when($search, function ($query, $search) {
            return $query->where('title', 'LIKE', "%{$search}%");
        })->when($sortedBy, function ($query, $sortedBy) {
            if ($sortedBy == "newest") {
                return $query->latest();
            } else if ($sortedBy == "oldest")
                return $query->oldest();
        })->where("status", 1)->paginate(1);
        return $this->successResponse([
            "procedures" => ProcedureClientResource::collection($procedures->load(["architecture", "process"])),
            "links" => ProcedureClientResource::collection($procedures)->response()->getData()->links,
            "meta" => ProcedureClientResource::collection($procedures)->response()->getData()->meta
        ], 200);
    }
    public function showBySlug($slug)
    {
        $procedure = Procedure::where('slug', $slug)->first();
        return $this->successResponse((new ProcedureDetailsClientResource($procedure->load(["files" => function ($query) {
            $query->withAllowedExtensions(['pdf', 'jpeg', 'png']); // فیلتر فایل‌ها بر اساس پسوند
        }, "architecture", "process"]))), 200);

    }
}
