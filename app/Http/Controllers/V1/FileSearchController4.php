<?php

namespace App\Http\Controllers\V1;
use App\Procedure;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FileSearchService;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\V1\Admin\ApiController;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use parallel\Runtime;
use App\Jobs\OcrPdfPageJob;
use App\Jobs\CollectOcrPagesResultsJob;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
class FileSearchController4 extends ApiController
{
    public function searchPdf()
    {
        $keyword = "قاسم";
        $filePath = public_path('storage/files/test.pdf');
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $totalPages = count($pdf->getPages());

        $pagesWithKeyword = [];
        $ocrQueue = [];

        for ($page = 1; $page <= $totalPages; $page++) {
            // بررسی متن مستقیم
            $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
            if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
                $pagesWithKeyword[] = $page;
            } else {
                $ocrQueue[] = $page;
            }
        }
        $batch = Bus::batch([])->name('OCR Batch')->dispatch(); // ایجاد batch خالی برای گرفتن id

        $jobs = [];
        // پاک کردن نتایج قبلی
        \Cache::connection('redis')->del("pdf:ocr:results");

        // ارسال صفحات OCR به صف
        foreach ($ocrQueue as $page) {
            $jobs[] = new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword);
            // OcrPdfPageJob::dispatch($page, $filePath, $pdftoppm, $keyword)->onQueue('ocr');
        }
        Bus::batch($jobs)
            ->then(function (Batch $batch) use ($filePath, $keyword, $pagesWithKeyword) {
                // ✅ این قسمت بعد از اتمام همه Jobها اجرا می‌شود
                // CollectOcrPagesResultsJob::dispatch($filePath);
                $key = "ocr_result_" . md5($filePath);
                $pages = Cache::get($key, []);

                // اینجا می‌تونی مثلا جمع عددی صفحات یا لیست صفحات را در DB ذخیره کنی
                $sum = array_sum($pages);
                Cache::forget($key);
                return response()->json([
                    'status' => 'jobs_dispatched',
                    'keyword' => $keyword,
                    'found_in_text' => $pagesWithKeyword,
                    'found_in_images' => $sum,
                    
                ]);
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('Batch failed: ' . $e->getMessage());
            })
            ->finally(function (Batch $batch) {
                Log::info('Batch processing finished.');
            })
            ->onQueue('ocr')
            ->dispatch();

    }
}

