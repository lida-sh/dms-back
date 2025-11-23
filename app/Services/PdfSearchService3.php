<?php

namespace App\Services;

use Psy\Readline\Hoa\Console;
use Smalot\PdfParser\Parser;
use Spatie\PdfToImage\Pdf;
use thiagoalessio\TesseractOCR\TesseractOCR;
use parallel\Runtime;
use App\Jobs\OcrPdfPageJob3;
use App\Jobs\CollectOcrPagesResultsJob3;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SearchPdfFileJob;
use Illuminate\Bus\Batchable;

class PdfSearchService3
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
            $filePath = public_path('storage/files/' . $dirPath . '/' . $file->filePath);
            // Log::info("filePath:" . $filePath);
            if (!file_exists($filePath)) {
                $results[] = [
                    'file_name' => $file->fileName,
                    'file_path' => $filePath,
                    'doc_name' => $file->process->title,
                    'found_in_text' => null,
                    'text_positions' => null,
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
            $textPositions = [];
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

                if (!empty(trim($text))) {
                    $pagePositions = $this->findKeywordPositions($text, $keyword, $page);
                    if (!empty($pagePositions)) {
                        $pagesWithKeyword[] = $page;
                        $textPositions[$page] = $pagePositions;
                    }
                }
                if ($pageHasRealImage) {
                    $ocrQueue[] = $page;
                }
            }
            $textKey = "text_pages_" . md5($file->filePath);
            $positionKey = "text_positions_" . md5($file->filePath);
            Cache::put($textKey, $pagesWithKeyword, now()->addMinutes(60));
            Log::info("PUT KEY: $textKey", ['path' => $file->filePath]);
            Cache::put($positionKey, $textPositions, now()->addMinutes(60));
            // ذخیره موقت صفحات دارای متن
            // $key = "text_pages_" . md5($filePath);
            // Cache::put($key, $pagesWithKeyword, now()->addMinutes(60));

            // مرحله 2: افزودن صفحات نیازمند OCR به لیست Job کلی
            foreach ($ocrQueue as $page) {
                $job = new OcrPdfPageJob3($page, $file->filePath, $pdftoppm, $keyword);
                $job->onConnection('database');
                $job->onQueue('ocr');
                $allJobs[] = $job;
            }

            // برای نمایش سریع به فرانت
            if (count($pagesWithKeyword)) {
                $results[] = [
                    'file_name' => $file->fileName,
                    // 'file_path'=> url('storage/files/' . $dirPath .'/'. $file->filePath),
                    'file_path' => $file->filePath,
                    'doc_name' => $file->process->title,
                    'architecture_name' => $file->process->architecture->title,
                    'code' => $file->process->code,
                    'found_in_text' => $pagesWithKeyword,
                    'text_positions' => $textPositions, // موقعیت‌های متن
                    'status' => count($ocrQueue) ? 'OCR pending' : 'complete',
                ];
            }
        }

        if (count($allJobs)) {
            $fileData = $files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_name' => $file->fileName,
                    'file_path' => $file->filePath,
                    'doc_name' => $file->process->title,
                    'architecture_name' => $file->process->architecture->title,
                    'code' => $file->process->code,
                ];
            })->toArray();
            Log::info('all job is ', $allJobs);
            Bus::batch($allJobs)
                ->then(function (Batch $batch) use ($keyword, $fileData, $searchId, $pagesWithKeyword, $textPositions) {
                    // بعد از تمام شدن OCR همه فایل‌ها
                    Log::info('✅ then() called for batch: ' . $batch->id);

                    CollectOcrPagesResultsJob3::dispatch($fileData, $keyword, $searchId, $pagesWithKeyword, $textPositions)->onQueue('ocr')->onConnection('database');

                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('Batch failed: ' . $e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    Log::info('Batch OCR finished.');
                })->onQueue('ocr')
                ->onConnection('database')
                ->dispatch();
            ;
        }

        // مرحله 4: پاسخ اولیه به فرانت
        return [
            'results' => $results,
            'status' => count($allJobs) ? 'processing ' . count($allJobs) . ' jobs' : 'complete',
        ];
    }
    private function findKeywordPositions($text, $keyword, $page)
    {
        $positions = [];
        $offset = 0;
        $keyword = mb_strtolower($keyword);
        $textLower = mb_strtolower($text);

        while (($pos = mb_stripos($textLower, $keyword, $offset)) !== false) {
            // محاسبه خط و موقعیت نسبی
            $textBefore = mb_substr($text, 0, $pos);
            $linesBefore = explode("\n", $textBefore);
            $lineNumber = count($linesBefore);
            $column = mb_strlen(end($linesBefore)) + 1;
            // استخراج متن اطراف برای context
            $startContext = max(0, $pos - 50);
            $endContext = min(mb_strlen($text), $pos + mb_strlen($keyword) + 50);
            $context = mb_substr($text, $startContext, $endContext - $startContext);

            $positions[] = [
                'page' => $page,
                'position' => $pos,
                'line' => $lineNumber,
                'column' => $column,
                'context' => $context,
                'length' => mb_strlen($keyword)
            ];
            $offset = $pos + mb_strlen($keyword);
        }

        return $positions;
    }
}
