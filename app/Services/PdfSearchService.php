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
    // public function searchPdf($filePath, $keyword)
    // {
    //     // $keyword = "قاسم";
    //     // $filePath = public_path('storage/files/test.pdf');
    //     $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
    //     $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';

    //     $parser = new Parser();
    //     $pdf = $parser->parseFile($filePath);
    //     $totalPages = count($pdf->getPages());

    //     $pagesWithKeyword = [];
    //     $ocrQueue = [];

    //     for ($page = 1; $page <= $totalPages; $page++) {
    //         // بررسی متن مستقیم
    //         $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
    //         if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
    //             $pagesWithKeyword[] = $page;
    //         } else {
    //             $ocrQueue[] = $page;
    //         }
    //     }
    //     $batch = Bus::batch([])->name('OCR Batch')->dispatch(); // ایجاد batch خالی برای گرفتن id

    //     $jobs = [];
    //     // پاک کردن نتایج قبلی
    //     \Cache::connection('redis')->del("pdf:ocr:results");

    //     // ارسال صفحات OCR به صف
    //     foreach ($ocrQueue as $page) {
    //         $jobs[] = new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword);
    //         // OcrPdfPageJob::dispatch($page, $filePath, $pdftoppm, $keyword)->onQueue('ocr');
    //     }
    //     Bus::batch($jobs)
    //         ->then(function (Batch $batch) use ($filePath, $keyword, $pagesWithKeyword) {
    //             // ✅ این قسمت بعد از اتمام همه Jobها اجرا می‌شود
    //             // CollectOcrPagesResultsJob::dispatch($filePath);
    //             $key = "text_pages_" . md5($filePath);
    //             $pages = Cache::get($key, []);

    //             // اینجا می‌تونی مثلا جمع عددی صفحات یا لیست صفحات را در DB ذخیره کنی
    //             // $sum = array_sum($pages);
    //             Cache::forget($key);
    //             return [
    //                 'found_in_text' => $pagesWithKeyword,
    //                 'found_in_images' => $pages,
    //             ];
    //         })
    //         ->catch(function (Batch $batch, Throwable $e) {
    //             Log::error('Batch failed: ' . $e->getMessage());
    //         })
    //         ->finally(function (Batch $batch) {
    //             Log::info('Batch processing finished.');
    //         })
    //         ->onQueue('ocr')
    //         ->dispatch();
    //     return [
    //         'found_in_text' => $pagesWithKeyword,
    //         'found_in_images' => [],
    //         'status' => 'OCR dispatched'
    //     ];

    // }
    public function searchFilesByArchitecture($files, $keyword)
    {
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';

        $allJobs = [];
        $results = [];
        $haspic = [];
        // برای هر فایل
        foreach ($files as $file) {
            $filePath = public_path('storage/files/processes/' . $file->filePath);

            if (!file_exists($filePath)) {
                $results[] = [
                    'file_name' => $file->fileName,
                    'process_name' => $file->process->title,
                    'error' => 'File not found',
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
                $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
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
                } elseif ($pageHasRealImage && empty(trim($text))) {
                    // صفحه تصویر غیرتکراری دارد و متنی ندارد ⇒ OCR لازم دارد
                    $ocrQueue[] = $page;
                }
                
            }

            // ذخیره موقت صفحات دارای متن
            $key = "text_pages_" . md5($filePath);
            Cache::put($key, ['text' => $pagesWithKeyword, 'image' => []], now()->addMinutes(60));

            // مرحله 2: افزودن صفحات نیازمند OCR به لیست Job کلی
            foreach ($ocrQueue as $page) {
                $allJobs[] = new OcrPdfPageJob($page, $filePath, $pdftoppm, $keyword);
            }

            // برای نمایش سریع به فرانت
            $results[] = [
                'file_name' => $file->file_name,
                'process_name' => $file->process->title,
                'found_in_text' => $pagesWithKeyword,
                'status' => count($ocrQueue) ? 'OCR pending' : 'complete',
            ];
        }
        // مرحله 3: اجرای همه صفحات OCR در یک Batch
        if (count($allJobs)) {
            Bus::batch($allJobs)
                ->then(function (Batch $batch) use ($keyword) {
                    // بعد از تمام شدن OCR همه فایل‌ها
                    CollectOcrPagesResultsJob::dispatch($keyword)->onConnection(queueConnection());
                })
                ->catch(function (Batch $batch, Throwable $e) {
                    Log::error('Batch failed: ' . $e->getMessage());
                })
                ->finally(function (Batch $batch) {
                    Log::info('Batch OCR finished.');
                })
                ->onQueue('ocr')->onConnection(queueConnection())
                ->dispatch();
        }

        // مرحله 4: پاسخ اولیه به فرانت
        return response()->json([
            'keyword' => $keyword,
            'results' => $results,
            'status' => count($allJobs) ? 'processing ' . count($allJobs) . ' jobs' : 'complete',
        ]);


    }

}