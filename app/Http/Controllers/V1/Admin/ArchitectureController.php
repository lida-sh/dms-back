<?php

namespace App\Http\Controllers\V1\Admin;

use App\Architecture;




use App\ArchitectureFile;
use App\Http\Resources\ArchitectureResource;
use App\Http\Resources\ProcessResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SebastianBergmann\Environment\Console;


class ArchitectureController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $architectures = Architecture::all();
        return response()->json($architectures, 200);
        // return $this->successResponse($architectures, 200);
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
            "title" => "required|string|unique:architectures,title",
            "code" => "required|unique:architectures,code",
            "files.*" => "file|max:2048",
            "type" => "required"
        ]);

        $allowedExtensions = ['bpm', 'jpg', 'jpeg', 'png', 'tiff', 'docx', 'doc', 'gif', 'pdf', 'pptx'];
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

        $architecture = Architecture::create([
            "title" => $request->title,
            "code" => $request->code,
            "description" => $request->description,
            "type" => $request->type,
            "user_id" => auth()->user()->id

        ]);
        if ($request->hasFile('files')) {
            foreach ($request->file("files") as $file) {
                $fileName = $file->getClientOriginalName();
                $filePath = time() . '.' . $file->getClientOriginalName();
                $file->storeAs('/images/architectures', $filePath, 'public');
                ArchitectureFile::create([
                    "architecture_id" => $architecture->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath
                ]);
            }


        }
        DB::commit();
        // return response()->json($data, $code);
        return $this->successResponse($architecture, 201);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Architecture $architecture)
    {

        return $this->successResponse((new ArchitectureResource($architecture->load("files"))), 200);
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
        $architecture = Architecture::findOrFail(($id));
        $architecture->update([
            "title" => $request->title,
            "code" => $request->code,
            "type" => $request->type,
            "description" => $request->description,
        ]);
        if ($request->has("fileIdsForDelete")) {
            foreach ($request->fileIdsForDelete as $fileId) {
                $file = ArchitectureFile::findOrFail($fileId);
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
                $file->storeAs('/images/architectures', $filePath, 'public');
                ArchitectureFile::create([
                    "architecture_id" => $architecture->id,
                    "fileName" => $fileName,
                    "filePath" => $filePath
                ]);
            }
        }
        DB::commit();
        return $this->successResponse($architecture, 200);

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
    public function getProcessesOfArchitecture(Architecture $architecture)
    {
        $processes = $architecture->processes;
        return $this->successResponse(ProcessResource::collection($processes), 200);
    }

}
