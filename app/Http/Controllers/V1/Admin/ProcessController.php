<?php

namespace App\Http\Controllers\V1\Admin;


use App\Http\Controllers\Controller;
use App\Http\Resources\ProcessResource;
use App\Process;
use App\ProcessFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class ProcessController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // dd($request->all());
        $search = $request->input("search");
        $architecture_id = $request->input("architecture_id");
        $status = $request->input("status");
        $sortedBy = $request->input("sortedBy");
        $processes = Process::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
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
        })->paginate(2);
        return $this->successResponse([
            "processes" => ProcessResource::collection($processes->load("architecture")),
            "links" => ProcessResource::collection($processes)->response()->getData()->links,
            "meta" => ProcessResource::collection($processes)->response()->getData()->meta
        ], 200);
        // return response()->json($processes, 200);
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
            "title" => "required|string|unique:processes,title",
            "code" => "required|string|unique:processes,code",
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

        $process = Process::create([
            "title" => $request->title,
            "code" => $request->code,
            "status" => $request->status,
            "description" => $request->description,
            "architecture_id" => $request->architecture_id,
            "user_id" => auth()->user()->id,
            "notification_date" => $request->notification_date

        ]);
        if ($request->hasFile('files')) {
            foreach ($request->file("files") as $file) {
                $fileName = $file->getClientOriginalName();
                // $filePath = time() . '.' . $file->getClientOriginalName();
                $filePath = time() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('/files/processes', $filePath, 'public');
                ProcessFile::create([
                    "process_id" => $process->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath,
                    "status" => 1
                ]);
            }


        }
        DB::commit();
        // return response()->json($data, $code);
        return $this->successResponse($process, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Process $process)
    {
        return $this->successResponse((new ProcessResource($process->load("files")->load("architecture"))), 200);
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
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            "architecture_id" => "required|integer",
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
        $process = Process::findOrFail($id);
        $process->slug = null;
        $process->update([
            "architecture_id" => $request->architecture_id,
            "title" => $request->title,
            "code" => $request->code,
            "description" => $request->description,
            "notification_date" => $request->notification_date
        ]);
        if ($request->has("fileIdsForDelete")) {
            foreach ($request->fileIdsForDelete as $fileId) {
                $file = ProcessFile::findOrFail($fileId);
                if (file_exists($file->filePath)) {
                    unlink($file->filePath);
                }
                $file->delete();
            }
        }
        if ($request->has("files") && $request->file("files") !== null) {
            foreach ($request->file("files") as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = time() . '.' . $file->getClientOriginalName();
                $file->storeAs('/files/processes', $filePath, 'public');
                ProcessFile::create([
                    "process_id" => $process->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath
                ]);
            }
        }
        DB::commit();
        return $this->successResponse($process, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
    public function showBySlug($slug)
    {
        $process = Process::where('slug', $slug)->first();
        return $this->successResponse((new ProcessResource($process->load("files")->load("architecture"))), 200);

    }
}
