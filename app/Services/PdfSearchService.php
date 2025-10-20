<?php

namespace App\Services;
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
use App\Jobs\SearchPdfFileJob;

class PdfSearchService
{
    public function searchPdf($filePath, $keyword)
    {
        // $keyword = "قاسم";
        // $filePath = public_path('storage/files/test.pdf');
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
                // $sum = array_sum($pages);
                Cache::forget($key);
                return [
                    'found_in_text' => $pagesWithKeyword,
                    'found_in_images' => $pages,
                ];
            })
            ->catch(function (Batch $batch, Throwable $e) {
                Log::error('Batch failed: ' . $e->getMessage());
            })
            ->finally(function (Batch $batch) {
                Log::info('Batch processing finished.');
            })
            ->onQueue('ocr')
            ->dispatch();
        return [
            'found_in_text' => $pagesWithKeyword,
            'found_in_images' => [],
            'status' => 'OCR dispatched'
        ];

    }
    public function searchFilesByArchitecture($files, $keyword)
    {
        
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';

        $allJobs = [];
        $results = [];

        // برای هر فایل
        foreach ($files as $file) {
            $filePath = public_path('storage/files/processes' . $file->filePath);

            if (!file_exists($filePath)) {
                $results[] = [
                    'file_name' => $file->file_name,
                    'process_name' => $file->process_name,
                    'error' => 'File not found',
                ];
                continue;
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $totalPages = count($pdf->getPages());
            $pagesWithKeyword = [];
            $ocrQueue = [];

            // مرحله 1: جستجوی مستقیم در متن PDF
            for ($page = 1; $page <= $totalPages; $page++) {
                $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
                if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
                    $pagesWithKeyword[] = $page;
                } else {
                    $ocrQueue[] = $page;
                }
            }

            // ذخیره موقت صفحات دارای متن
            $key = "ocr_result_" . md5($filePath);
            Cache::put($key, ['text' => $pagesWithKeyword, 'image' => []], now()->addMinutes(60));

            // مرحله 2: افزودن صفحات نیازمند OCR به لیست Job کلی
            foreach ($ocrQueue as $page) {
                $allJobs[] = new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword);
            }

            // برای نمایش سریع به فرانت
            $results[] = [
                'file_name' => $file->fileName,
                'process_name' => $file->process_name,
                'found_in_text' => $pagesWithKeyword,
                'status' => count($ocrQueue) ? 'OCR pending' : 'complete',
            ];
        }

        // مرحله 3: اجرای همه صفحات OCR در یک Batch
        if (count($allJobs)) {
            Bus::batch($allJobs)
                ->then(function (Batch $batch) use ($keyword) {
                    // بعد از تمام شدن OCR همه فایل‌ها
                    CollectOcrPagesResultsJob::dispatch($keyword);
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('Batch failed: ' . $e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    Log::info('Batch OCR finished.');
                })
                ->onQueue('ocr')
                ->dispatch();
        }

        // مرحله 4: پاسخ اولیه به فرانت
        return response()->json([
            'keyword' => $keyword,
            'results' => $results,
            'status' => count($allJobs) ? 'processing' : 'complete',
        ]);
        

    }
    // public function searchFilesByArchitecture($files, $keyword)
    // {
    //     $jobs = [];

    //     foreach ($files as $file) {
    //         $jobs[] = new SearchPdfFileJob($file, $keyword);
    //     }
    //     $batch = Bus::batch($jobs)
    //     ->then(function (Batch $batch) {
    //         // وقتی همه فایل‌ها پردازش شدند
    //         Log::info('✅ All PDF search jobs completed.');
    //     })
    //     ->catch(function (Batch $batch, Throwable $e) {
    //         Log::error('❌ Batch failed: ' . $e->getMessage());
    //     })
    //     ->finally(function (Batch $batch) {
    //         Log::info('🎯 Batch finished.');
    //     })
    //     ->dispatch();

    // return [
    //     'status' => 'dispatched',
    //     'batch_id' => $batch->id,
    //     'message' => 'PDF search jobs are running in background.'
    // ];
        

    // }

}