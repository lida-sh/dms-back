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

class PdfSearchService5
{
    use Batchable;

    public function searchFilesByArchitecture($files, $keyword, $dirPath, $searchId)
    {

        $pdftoppm = "C:\poppler-25.11.0\Library\bin\\pdftoppm.exe";
        $pdftotext = "C:\poppler-25.11.0\Library\bin\\pdftotext.exe";

        $allJobs = [];
        $results = [];
        // $failedResults = [];

        // برای هر فایل
        foreach ($files as $file) {
            $filePath = public_path('storage/files/' . $dirPath . '/' . $file->filePath);
            Log::info("filePath:" . $filePath);
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
            $pdfimages = "C:\poppler-25.11.0\Library\bin\\pdfimages.exe";
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
            mb_internal_encoding('UTF-8');
            for ($page = 1; $page <= $totalPages; $page++) {
                $textRaw = shell_exec($pdftotext .
                    ' -f ' . $page .
                    ' -l ' . $page .
                    ' -layout -q ' .
                    escapeshellarg($filePath) . ' -');
                Log::info("textRaw" . $textRaw. "page" . $page);
                $textRaw = $this->removeUnicodeControls($textRaw);
                // $textRaw = $this->fixPdfExtractErrors($textRaw);
                
                $textRaw = $this->normalizePersianText($textRaw);
                 
                if ($textRaw !== null) {
                    // تلاش اتوماتیک برای تبدیل به UTF-8
                    $detected = mb_detect_encoding($textRaw, [
                        'UTF-8',
                        'UTF-16',
                        'UTF-16BE',
                        'UTF-16LE',
                        'UTF-32',
                        'UTF-32BE',
                        'UTF-32LE',
                        'ASCII',
                        'ISO-8859-6', // تنها عربی موجود در PHP شما
                    ], true);
                    if (!$detected) {
                        $detected = 'UTF-8';
                    }
                    $text = mb_convert_encoding($textRaw, 'UTF-8', $detected);
                     
                } else {
                    $text = '';
                }
                // $text = $this->removeUnicodeControls($text);
                // $text = $this->fixPdfExtractErrors($text);
                
                // $text = $this->normalizePersianText($text);
                // Log::info("text:$text");
                $pageHasRealImage = true;
                // if (isset($images[$page])) {
                //     foreach ($images[$page] as $img) {
                //         $sizeKey = $img['width'] . 'x' . $img['height'];
                //         if (!in_array($sizeKey, $logoSizes)) {
                //             // تصویر غیرتکراری در صفحه وجود دارد
                //             $pageHasRealImage = true;
                //             break;
                //         }
                //     }
                // }

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
            Log::info('all job is '. count($allJobs));
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
    private function normalizePersianText(string $s): string
    {
        if ($s === '')
            return $s;

        // حذف کاراکترهای کنترلی رایج
        $s = str_replace("\r", '', $s);

        // حذف soft hyphen و کارکترهای نامرئی
        $s = str_replace("\xC2\xAD", '', $s); // U+00AD (soft hyphen) - UTF-8
        $s = preg_replace('/\p{Cc}+/u', '', $s); // کنترل‌ها

        // حذف یا تبدیل ZWJ / ZWNJ (در مورد شما بهتر است حذف شود تا جستجو ساده شود)
        $s = str_replace("\u{200C}", '', $s); // ZWNJ
        $s = str_replace("\u{200D}", '', $s); // ZWJ

        // تبدیل شکستِ خطِ همراه با  hyphen مانند "اصلا-\nحی" -> "اصلاحی"
        $s = preg_replace("/-\s*\n\s*/u", '', $s);

        // سپس تبدیل بقیه newlineها به فاصله تا کلمه‌ها به هم نچسبند
        $s = preg_replace("/\n+/", ' ', $s);
        // فشرده‌سازی فضاها
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = trim($s);

        // تبدیل معادل‌های عربی به فارسی
        $map = [
            'ي' => 'ی',
            'ى' => 'ی',
            'ك' => 'ک',
            'ؤ' => 'و',
            'إ' => 'ا',
            'أ' => 'ا',
            'ٱ' => 'ا',
            // 'ئ' => 'ی',
            // اعداد عربی -> لاتین (در صورت نیاز)
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
            // اگر دوست داری ارقام فارسی به لاتین هم مپ کن
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
        ];
        $s = strtr($s, $map);

        return $s;
    }

    private function normalizeKeyword(string $kw): string
    {
        $kw = trim($kw);
        $kw = mb_convert_case($kw, MB_CASE_LOWER, 'UTF-8');
        $kw = $this->normalizePersianText($kw);
        return $kw;
    }
    private function fixPdfExtractErrors(string $text): string
    {
        $map = [
            // اشتباهات رایج pdftotext در PDF فارسی
            'ط' => 'ت',   // اطالعات → اطلاعات
            'ص' => 'س',   // اصالحی → اصلاحی
            // 'ا' => 'ل',   // اطالعات → اطلاعات (گاهی ل می‌شود ا)
            // 'ل' => 'ا',   // اطالعات → اطلاعات (گاهی ل می‌شود ا)
            // // بسته به PDF شاید لازم باشد بیشتر اضافه کنید:

            // حرف‌های عربی → فارسی
            'ي' => 'ی',
            'ى' => 'ی',
            'ۀ' => 'ه',
            'ة' => 'ه',
            'ك' => 'ک',
            'ؤ' => 'و',
        ];

        return strtr($text, $map);
    }
    private function removeUnicodeControls(string $text): string
{
    // حذف کاراکترهای جهت‌دهی بی‌صدا (directional marks)
    $controls = [
        "\u{202A}", // LRE
        "\u{202B}", // RLE
        "\u{202D}", // LRO
        "\u{202E}", // RLO
        "\u{202C}", // PDF
        "\u{2066}", // LRI
        "\u{2067}", // RLI
        "\u{2068}", // FSI
        "\u{2069}", // PDI
        "\u{200F}", // RTL mark
        "\u{200E}", // LTR mark
        "\u{061C}", // Arabic letter mark
    ];

    return str_replace($controls, '', $text);
}

}
