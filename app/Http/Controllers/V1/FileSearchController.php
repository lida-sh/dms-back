<?php

namespace App\Http\Controllers\V1;
use App\Procedure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileSearchService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\V1\Admin\ApiController;
class FileSearchController extends ApiController
{
    protected $fileSearchService;

    public function __construct(FileSearchService $fileSearchService)
    {
        $this->fileSearchService = $fileSearchService;
        
    }
    public function search()
    {
       
       $results = [];
       $precedure = Procedure::findOrFail(32);
        foreach ($precedure->files as $file) {
            $fileName = $file->fileName;                                                                                                                         
            $fullPath = public_path('storage/files/procedures/' . $file->filePath);
            if (file_exists($fullPath)) {
                try {
               $result = $this->fileSearchService->searchInFile($fullPath, "تلفن"); 
               $results[$fileName] = $result;
               } catch (\Exception $e) {
                $results[$fileName] = [
                    'error' => $e->getMessage(),
                    'matches' => 0
                ];
            }
            
        }
        
        
 
    }
    return $results;
}
    
}
// $result = $this->fileSearchService->searchInFile($fullPath, $searchTerm);
        // $searchTerm = $request->search_term;

        // foreach ($request->file('files') as $file) {
        //     $fileName = $file->getClientOriginalName();
        //     $filePath = $file->store('temp');
        //     $fullPath = storage_path('app/' . $filePath);

        //     try {
        //         $result = $this->fileSearchService->searchInFile($fullPath, $searchTerm);
        //         $results[$fileName] = $result;
        //     } catch (\Exception $e) {
        //         $results[$fileName] = [
        //             'error' => $e->getMessage(),
        //             'matches' => 0
        //         ];
        //     }

        //     // حذف فایل موقت
        //     Storage::delete($filePath);
        // }