<?php

namespace App\Http\Controllers\V1;
use App\Procedure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileSearchService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\V1\Admin\ApiController;
use Smalot\PdfParser\Parser;
class FileSearchController extends ApiController
{
    protected $fileSearchService;

    public function __construct(FileSearchService $fileSearchService)
    {
        $this->fileSearchService = $fileSearchService;
        
    }
    public function search()
    {
       
    //    $results = [];
    //    $precedure = Procedure::findOrFail(32);
    //     foreach ($precedure->files as $file) {
    //         $fileName = $file->fileName;                                                                                                                         
    //         $fullPath = public_path('storage/files/procedures/' . $file->filePath);
    //         if (file_exists($fullPath)) {
    //             try {
    //            $result = $this->FileSearchService->searchInPdf($fullPath, "تلفن"); 
    //            $results[$fileName] = $result;
    //            } catch (\Exception $e) {
    //             $results[$fileName] = [
    //                 'error' => $e->getMessage(),
    //                 'matches' => 0
    //             ];
    //         }
            
    //     }
        
         $fullPath = public_path('/storage/files/test.pdf');
         if (file_exists($fullPath)) {
            try {
                return $this->fileSearchService->searchInPdf($fullPath, "کد"); 
         }catch (\Exception $e) {
                $results = [
                    'error' => $e->getMessage(),
                    'matches' => 0
                ];
            }
    return $results;}
}
function normalizePersianText($text)
{
    if (empty($text)) {
        return $text;
    }

    // تبدیل encoding به UTF-8 اگر نیست
    if (!mb_detect_encoding($text, 'UTF-8', true)) {
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
    }

    // جایگزینی کاراکترهای عربی با معادل فارسی
    $arabicToPersian = [
        'ك' => 'ک', 'ي' => 'ی', 'ة' => 'ه', 'ؤ' => 'و',
        'أ' => 'ا', 'إ' => 'ا', 'ٱ' => 'ا', 'ۂ' => 'ه',
        'ۃ' => 'ه', 'ۀ' => 'ه', 'ﮏ' => 'ک', 'ﮐ' => 'ک',
        '﮳' => 'ه', '﮴' => 'ه', '﮵' => 'ه', '﮶' => 'ه',
        'ﺄ' => 'ا', 'ﺎ' => 'ا', 'ﺐ' => 'ب', 'ﺑ' => 'ب',
        'ﺒ' => 'ب', 'ﺓ' => 'ه', 'ﻚ' => 'ک', 'ﻛ' => 'ک',
        'ﻜ' => 'ک'
    ];

    // حذف کاراکترهای کنترل و غیر ضروری
    $text = preg_replace('/[\x00-\x1F\x7F-\xA0]/u', '', $text);
    
    // جایگزینی کاراکترها
    $text = strtr($text, $arabicToPersian);
    
    // حذف فاصله‌های اضافه
    $text = preg_replace('/\s+/u', ' ', $text);
    // حذف فاصله از ابتدا و انتها
    $text = trim($text);
    
    return $text;
}
function searchInPdfWithNormalization($filePath, $searchTerm)
{
    $parser = new Parser();
    $pdf = $parser->parseFile($filePath);
    $text = mb_convert_encoding($pdf->getText(), 'UTF-8', 'auto');
    
    // نرمال‌سازی با PersianTools
    $normalizedText = PersianTools::normalizePersianText($text);
    $normalizedSearch = PersianTools::normalizePersianText($searchTerm);
    
    return mb_stripos($normalizedText, $normalizedSearch) !== false;
}

function searchInPdf($filePath, $searchTerm)
{
    $parser = new Parser();
    $pdf = $parser->parseFile($filePath);
    $text = mb_convert_encoding($pdf->getText(), 'UTF-8', 'auto');
    
    // نرمال‌سازی برای جستجوی بهتر
    $normalizedText = normalizer_normalize($text, Normalizer::NFC);
    $normalizedSearch = normalizer_normalize($searchTerm, Normalizer::NFC);
    
    return mb_stripos($normalizedText, $normalizedSearch) !== false;
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