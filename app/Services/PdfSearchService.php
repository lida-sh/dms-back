<?php

namespace App\Services;

use Psy\Readline\Hoa\Console;
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
use Illuminate\Bus\Batchable;

class PdfSearchService
{
    use Batchable;

    public function searchFilesByArchitecture($files, $keyword, $dirPath, $searchId)
    {

        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';

        $allJobs = [];
        $results = [];
        // $failedResults = [];

        // برای هر فایل
        foreach ($files as $file) {
            $filePath = public_path('storage/files/' . $dirPath .'/'. $file->filePath);
            Log::info("filePath:".$filePath);
            if (!file_exists($filePath)) {
                $results[] = [
                    'file_name' => $file->file_name,
                    'file_path'=> $filePath,
                    'doc_name' => $file->process->title,
                    'found_in_text' => null,
                    'status' => 'file not found',
                ];
                continue;
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            $totalPages = count($pdf->getPages());
            $pagesWithKeyword = [];
            $ocrQueue = [];
            $pdfimages = '"C:\\poppler-25.07.0\\Library\\bin\\pdfimages.exe"';
            $imagesList = shell_exec($pdfimages . ' -list ' . escapeshellarg($filePath));

            // هر خط از اطلاعات تصاویر شامل page، num، width، height و type است
            $lines = preg_split('/\r\n|\r|\n/', trim($imagesList));

            // حذف هدر و خطوط خالی
            $images = [];
            foreach ($lines as $line) {
                if (preg_match('/^\s*(\d+)\s+(\d+)\s+(\w+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                    $page = (int) $m[1];
                    $width = (int) $m[4];
                    $height = (int) $m[5];
                    $images[$page][] = ['width' => $width, 'height' => $height];
                }
            }
            // شمارش ابعاد تکراری
            $allSizes = [];
            foreach ($images as $pageImages) {
                foreach ($pageImages as $img) {
                    $key = $img['width'] . 'x' . $img['height'];
                    $allSizes[$key] = ($allSizes[$key] ?? 0) + 1;
                }
            }

            // اندازه‌هایی که در بیش از نصف صفحات تکرار شدن رو ثابت در نظر بگیر
            $logoSizes = [];
            $totalPages = count($pdf->getPages());
            foreach ($allSizes as $size => $count) {
                if ($count >= ($totalPages / 2)) {
                    $logoSizes[] = $size;
                }
            }

            for ($page = 1; $page <= $totalPages; $page++) {
                $text = shell_exec($pdftotext .
                    ' -f ' . $page .
                    ' -l ' . $page .
                    ' -layout -q ' .
                    escapeshellarg($filePath) . ' -');
                // $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
                $pageHasRealImage = false;
                if (isset($images[$page])) {
                    foreach ($images[$page] as $img) {
                        $sizeKey = $img['width'] . 'x' . $img['height'];
                        if (!in_array($sizeKey, $logoSizes)) {
                            // تصویر غیرتکراری در صفحه وجود دارد
                            $pageHasRealImage = true;
                            break;
                        }
                    }
                }
                // $pdfimages = '"C:\\poppler-25.07.0\\Library\\bin\\pdfimages.exe"';
                // $imageInfo = shell_exec($pdfimages . ' -f ' . $page . ' -l ' . $page . ' -list ' . escapeshellarg($filePath));
                // $hasImages = preg_match('/^\s*\d+/m', trim($imageInfo));
                if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
                    $pagesWithKeyword[] = $page;
                } elseif ($pageHasRealImage) {
                    // صفحه تصویر غیرتکراری دارد و متنی ندارد ⇒ OCR لازم دارد
                    $ocrQueue[] = $page;
                }
            }

            // ذخیره موقت صفحات دارای متن
            $key = "text_pages_" . md5($filePath);
            Cache::put($key, $pagesWithKeyword, now()->addMinutes(60));

            // مرحله 2: افزودن صفحات نیازمند OCR به لیست Job کلی
            foreach ($ocrQueue as $page) {
                $job = new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword);
                $job->onConnection('database');
                $job->onQueue('ocr');
                $allJobs[] = $job;

                // $allJobs[] = (new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword));
            }

            // برای نمایش سریع به فرانت
            // if (count($pagesWithKeyword)) {
                $results[] = [
                    'file_name' => $file->fileName,
                    'file_path'=> $filePath,
                    'doc_name' => $file->process->title,
                    'architecture_name' => $file->process->architecture->title,
                    'code' => $file->process->code,
                    'found_in_text' => $pagesWithKeyword,
                    'status' => count($ocrQueue) ? 'OCR pending' : 'complete',
                ];
            // }
        }
        foreach ($allJobs as $index => $job) {
            $queueName = property_exists($job, 'queue') ? $job->queue : 'not-set';
            $connectionName = property_exists($job, 'connection') ? $job->connection : 'not-set';
            Log::info("🔍 Job #{$index} => Queue: {$queueName}, Connection: {$connectionName}, Class: " . get_class($job));
        }
        Log::info('Queue config before dispatch: ', [
            'connection' => config('queue.default'),
            'driver' => config('queue.connections.' . config('queue.default')),
        ]);
        // مرحله 3: اجرای همه صفحات OCR در یک Batch
        if (count($allJobs)) {
            Log::info('all job is ', $allJobs);
            Bus::batch($allJobs)
                ->then(function (Batch $batch) use ($keyword, $files) {
                    // بعد از تمام شدن OCR همه فایل‌ها
                    Log::info('✅ then() called for batch: ' . $batch->id);
                    // Log::info('✅ All OCR jobs completed. Dispatching collector job...');
                    CollectOcrPagesResultsJob::dispatch($files, $keyword)->onQueue('ocr')->onConnection('database');
                    // if ($this->isLastBatch()) { // ← شرط کن که فقط یکبار اجرا شود
                    //     Log::info('✅ All OCR jobs completed. Dispatching collector job...تست chain');
                    //     CollectOcrPagesResultsJob::dispatch($files, $keyword)
                    //         ->onQueue('ocr')
                    //         ->onConnection('database');
                    // }
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('Batch failed: ' . $e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    Log::info('Batch OCR finished.');
                })->onQueue('ocr')
                ->onConnection('database')
                ->dispatch();;
        }

        // مرحله 4: پاسخ اولیه به فرانت
        return [
            'results' => $results,
            'status' => count($allJobs) ? 'processing ' . count($allJobs) . ' jobs' : 'complete',
        ];
    }
}
