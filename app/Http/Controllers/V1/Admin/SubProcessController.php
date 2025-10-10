<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubProcessResource;
use App\SubProcess;
use App\SubProcessFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Hekmatinasser\Verta\Verta;
use App\Services\DataConverter;
class SubProcessController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $search = $request->input("search");
        $architecture_id = $request->input("architecture_id");
        $process_id = $request->input("process_id");
        $status = $request->input("status");
        $sortedBy = $request->input("sortedBy");
        $processes = SubProcess::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
        })->when($process_id, function ($query, $process_id) {
            return $query->where("process_id", $process_id);
        })->when($status, function ($query, $status) {
            if ($status == 1) {
                return $query->where("status", $status);
            } else {
                return $query->where("status", 0);
            }

        })->when($search, function ($query, $search) {
            return $query->where('title', 'LIKE', "%{$search}%");
        })->when($sortedBy, function ($query, $sortedBy) {
            if ($sortedBy == "newest") {
                return $query->latest();
            } else if ($sortedBy == "oldest")
                return $query->oldest();
        })->paginate(10);
        return $this->successResponse([
            "subProcesses" => SubProcessResource::collection($processes->load(["architecture", "process", "user"])),
            "links" => SubProcessResource::collection($processes)->response()->getData()->links,
            "meta" => SubProcessResource::collection($processes)->response()->getData()->meta
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            "architecture_id" => "required|integer",
            "process_id" => "required|integer",
            "title" => "required|string|unique:sub_processes,title",
            "code" => "required|string|unique:sub_processes,code",
            "status" => "required|string",
            "files.*" => "file|max:2048",
        ]);
        $allowedExtensions = ['bpm', 'jpg', 'jpeg', 'png', 'tiff', 'docx', 'doc', 'gif', 'pdf'];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) {
                    $validator->errors()->add('files', 'فایل ' . $file->getClientOriginalName() . ' معتبر نیست.');
                    return $this->errorResponse($validator->messages(), 422);
                }
            }
        }
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        DB::beginTransaction();

        $subProcess = SubProcess::create([
            "title" => $request->title,
            "code" => $request->code,
            "status" => $request->status,
            "description" => $request->description,
            "architecture_id" => $request->architecture_id,
            "process_id" => $request->process_id,
            "user_id" => auth()->user()->id,
            "notification_date" => $request->notification_date,

        ]);
        if ($request->hasFile('files')) {
            foreach ($request->file("files") as $file) {
                $fileName = $file->getClientOriginalName();
                // $filePath = time() . '.' . $file->getClientOriginalName();
                $filePath = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('/files/sub-processes', $filePath, 'public');
                SubProcessFile::create([
                    "sub_process_id" => $subProcess->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath,
                    "status" => 1
                ]);
            }


        }
        DB::commit();
        // return response()->json($data, $code);
        return $this->successResponse($subProcess, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(SubProcess $subProcess)
    {
        return $this->successResponse((new SubProcessResource($subProcess->load(["files", "architecture", "process"]))), 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        
        $validator = Validator::make($request->all(), [
            "architecture_id" => "required|integer",
            "process_id" => "required|integer",
            "title" => "string|unique:architectures,title," . $id,
            "code" => "string|unique:architectures,code," . $id,
            "files.*" => "file|max:2048",
        ]);

        $allowedExtensions = ['bpm', 'jpg', 'jpeg', 'png', 'tiff', 'docx', 'doc', 'gif', 'pdf'];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                if (!in_array($file->getClientOriginalExtension(), $allowedExtensions)) {
                    $validator->errors()->add('files', 'فایل ' . $file->getClientOriginalName() . ' معتبر نیست.');
                    return $this->errorResponse($validator->messages(), 422);
                }
            }
        }
        if ($validator->fails()) {
            return $this->errorResponse($validator->messages(), 422);
        }

        DB::beginTransaction();
        $subProcess = SubProcess::findOrFail($id);
        $subProcess->slug = null;
        $subProcess->update([
            "architecture_id" => $request->architecture_id,
            "process_id" => $request->process_id,
            "title" => $request->title,
            "code" => $request->code,
            "status" => $request->status,
            "description" => $request->description,
            "notification_date" => DataConverter::convertToGregorian($request->notification_date),
        ]);
        if ($request->has("fileIdsForDelete")) {
            foreach ($request->fileIdsForDelete as $fileId) {
                $file = SubProcessFile::findOrFail($fileId);
                if (file_exists($file->filePath)) {
                    unlink($file->filePath);
                }
                $file->delete();
            }
        }
        if ($request->has("files") && $request->file("files") !== null) {
            foreach ($request->file("files") as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('/files/sub-processes', $filePath, 'public');
                SubProcessFile::create([
                    "sub_process_id" => $subProcess->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath
                ]);
            }
        }
        DB::commit();
        return $this->successResponse($subProcess, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $subProcess = SubProcess::findOrFail($id);
        foreach ($subProcess->files as $file) {
            $fullPath = public_path('storage/files/sub-processes/'.$file->filePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            SubProcessFile::findOrFail($file->id)->delete();
        }
        $subProcess->delete();
        return $this->successResponse(1, 200);
    }
    public function showBySlug($slug)
    {
        $process = SubProcess::where('slug', $slug)->first();
        return $this->successResponse((new SubProcessResource($process->load(["files", "architecture", "process"]))), 200);

    }
}
