<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProcedureResource;
use App\Procedure;
use App\ProcedureFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Hekmatinasser\Verta\Verta;

class ProcedureController extends ApiController
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
        $docType = $request->input("docType");
        $sortedBy = $request->input("sortedBy");
        $procedures = Procedure::query()->when($architecture_id, function ($query, $architecture_id) {
            return $query->where("architecture_id", $architecture_id);
        })->when($process_id, function ($query, $process_id) {
            return $query->where("process_id", $process_id);
        })->when($docType, function ($query, $docType) {
            return $query->where("docType", $docType);
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
            "procedures" => ProcedureResource::collection($procedures->load(["architecture", "process", "user"])),
            "links" => ProcedureResource::collection($procedures)->response()->getData()->links,
            "meta" => ProcedureResource::collection($procedures)->response()->getData()->meta
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
            "title" => "required|string|unique:procedures,title",
            "code" => "required|string|unique:procedures,code",
            "status" => "required|string",
            "docType" => "required|string",
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

        $procedure = Procedure::create([
            "title" => $request->title,
            "code" => $request->code,
            "status" => $request->status,
            "docType" => $request->docType,
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
                $file->storeAs('/files/procedures', $filePath, 'public');
                ProcedureFile::create([
                    "procedure_id" => $procedure->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath,
                    "status" => 1
                ]);
            }


        }
        DB::commit();
        // return response()->json($data, $code);
        return $this->successResponse($procedure, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $precedure = Procedure::findOrFail($id);
        return $this->successResponse((new ProcedureResource($precedure->load(["files", "architecture", "process"]))), 200);

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
        //    dd($request->all());
        // 
        $validator = Validator::make($request->all(), [
            "architecture_id" => "required|integer",
            "process_id" => "required|integer",
            "title" => "string|unique:architectures,title," . $id,
            "code" => "string|unique:architectures,code," . $id,
            "status" => "required|string",
            "docType" => "required|string",
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
        $procedure = Procedure::findOrFail($id);
        $procedure->slug = null;
        $procedure->update([
            "architecture_id" => $request->architecture_id,
            "process_id" => $request->process_id,
            "title" => $request->title,
            "code" => $request->code,
            "status" => $request->status,
            "docType" => $request->docType,
            // "notification_date" => Verta::parse($request->notification_date)->datetime()->format('Y-m-d'),
            "notification_date" => $request->notification_date,
            "description" => $request->description,
        ]);
        if ($request->has("fileIdsForDelete")) {
            foreach ($request->fileIdsForDelete as $fileId) {
                $file = ProcedureFile::findOrFail($fileId);
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
                $file->storeAs('/files/procedures', $filePath, 'public');
                ProcedureFile::create([
                    "procedure_id" => $procedure->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath
                ]);
            }
        }
        DB::commit();
        return $this->successResponse($procedure, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $precedure = Procedure::findOrFail($id);
        
        foreach ($precedure->files as $file) {
            $fullPath = storage_path('app/public/files/procedures/'.$file->filePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            ProcedureFile::findOrFail($file->id)->delete();
        }
        $precedure->delete();
        return $this->successResponse(1, 200);
    }
    public function showBySlug($slug)
    {
        $process = Procedure::where('slug', $slug)->first();
        return $this->successResponse((new ProcedureResource($process->load(["files", "architecture", "process"]))), 200);

    }
}
