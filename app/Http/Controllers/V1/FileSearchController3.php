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
class FileSearchController3 extends ApiController
{
    public function searchInPdf(Request $request)
    {
        $keyword = "اصلاحی"; // متن یا کلمه مورد نظر
        $filePath = public_path('storage/files/test.pdf');

        if (!file_exists($filePath)) {
            return response()->json(['error' => 'file not found: ' . $filePath], 404);
        }

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $matchedPages = [];

        // کلیدواژه و نسخه برعکسش
        $normKeyword = $this->normalizePersian($keyword);
        // $keywordVariants = [$normKeyword, $this->mb_strrev($normKeyword)];
        $keywordVariants = [
            $normKeyword,
            $this->mb_strrev($normKeyword),
            preg_replace('/\s+/u', '', $normKeyword),               // حذف فاصله‌ها
            $this->mb_strrev(preg_replace('/\s+/u', '', $normKeyword)) // برعکس بدون فاصله
        ];
        foreach ($pages as $index => $page) {
            $raw = (string) $page->getText();
            // $norm = $this->normalizePersian($raw);
            $variants = $this->normalizeAndVariants($raw);
            foreach ($variants as $pageText) {
                foreach ($keywordVariants as $kv) {
                    if ($kv && mb_stripos($pageText, $kv, 0, 'UTF-8') !== false) {
                        $matchedPages[] = $index + 1;
                        break 2;
                    }
                }
            }
            // foreach ($variants as $v) {
            //     foreach ($keywordVariants as $kv) {
            //         if (mb_stripos($v, $kv, 0, 'UTF-8') !== false) {
            //             $matchedPages[] = $index + 1;
            //             break 2; // چون پیدا شد
            //         }
            //     }
            // }
            // foreach ($variants as $v) {
            //     if (mb_stripos($v, $normKeyword, 0, 'UTF-8') !== false) {
            //         $matchedPages[] = $index + 1;
            //         break; // چون پیدا شد
            //     } else {
            //         // اگر می‌خواهی برای دیباگ لاگ کن
            //         //Log::debug("page " . ($index + 1) . " snippet: " . mb_substr($norm, 0, 200, 'UTF-8'));
            //     }
            // }
            // استفاده از mb_stripos با UTF-8

        }

        return response()->json([
            'keyword' => $keyword,
            'pages' => $matchedPages
        ]);
    }

    public function debugPage(Request $request)
    {
        $pageNo = max(1, (int) $request->input('page', 1));
        $filePath = public_path('storage/files/test.pdf');

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();

        if (!isset($pages[$pageNo - 1])) {
            return response()->json(['error' => 'page not found']);
        }

        $raw = (string) $pages[$pageNo - 1]->getText();
        return response()->json([
            'page' => $pageNo,
            'raw_preview' => mb_substr($raw, 0, 1000, 'UTF-8'),
            'raw_base64' => base64_encode($raw),
            'normalized_preview' => mb_substr($this->normalizePersian($raw), 0, 500, 'UTF-8'),
        ]);
    }
    private function normalizePersian(string $text): string
    {

        if ($text === null || $text === '')
            return '';

        // ی و ک عربی -> فارسی
        $text = str_replace(['ي', 'ك'], ['ی', 'ک'], $text);

        // حذف نیم‌فاصله (ZWNJ)
        $text = preg_replace('/\x{200C}/u', '', $text);

        // حذف علائم ترکیبی
        $text = preg_replace('/\p{M}/u', '', $text);

        // حذف فاصله‌ها
        $text = preg_replace('/\s+/u', '', $text);

        return $text;
    }


    private function normalizeAndVariants(string $text): array
    {
        $norm = $this->normalizePersian($text);
        return [
            $norm,
            $this->mb_strrev($norm), // نسخه برعکس هم بررسی می‌کنیم
        ];
    }

    private function mb_strrev($str, $encoding = 'UTF-8')
    {
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        return implode('', array_reverse($chars));
    }

    public function searchOCR()
    {

        // exec('echo Test > C:\\xampp\\htdocs\\dms-back\\test.txt');
        // exec('"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe" -v 2>&1' , $output, $returnVar);
        // dd($output, $returnVar);

        $keyword = "کنطورچیان";

        $filePath = str_replace('/', DIRECTORY_SEPARATOR, public_path('storage/files/test.pdf'));
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';
        $pagesWithKeyword = [];
        // $pdfImages = new Pdf($filePath);
        // $pdfImages->setOutputFormat('png');
        // $totalPages = $pdfImages->getNumberOfPages();
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $pages = $pdf->getPages();
        $totalPages = count($pages);
        // $text = shell_exec('"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe" ' . escapeshellarg($filePath) . ' -layout -q -'); 
        // dd($text);
        for ($page = 1; $page <= $totalPages; $page++) {
            if (in_array($page, $pagesWithKeyword))
                continue;

            // تبدیل PDF به تصویر (ppm)

            $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');

            // بررسی اینکه متن موجود است یا نه
            if (!empty(trim($text))) {
                // متن مستقیم پیدا شد
                if (mb_stripos($text, $keyword) !== false) {
                    $pagesWithKeyword[] = $page;
                }
                continue; // به صفحه بعدی برو
            }
            $imagePath = str_replace('/', DIRECTORY_SEPARATOR, public_path("storage/files/_$page"));

            $cmd = $pdftoppm . " -f $page -l $page -singlefile -png "
                . escapeshellarg($filePath) . " "
                . escapeshellarg($imagePath) . " 2>&1";

            exec($cmd, $output, $return_var);
            if ($return_var !== 0 || !file_exists($imagePath . ".png")) {
                throw new \Exception("Image not created: " . $imagePath . ".png. Output: " . implode("\n", $output));
            }
            
            $ocrText = (new TesseractOCR($imagePath . ".png"))
                ->lang('fas') // زبان فارسی
                ->run();

            if (mb_stripos($ocrText, $keyword) !== false) {
                $pagesWithKeyword[] = $page;
            }

            // حذف فایل موقت
            unlink($imagePath . ".png");

        }


        sort($pagesWithKeyword);

        return response()->json([
            'keyword' => $keyword,
            'pages' => $pagesWithKeyword
        ]);
    }
    public function paraller()
    {


        $keyword = "قاسم";
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, public_path('storage/files/test.pdf'));
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';
        $pagesWithKeyword = [];

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $totalPages = count($pdf->getPages());

        // تعداد پردازش موازی
        $parallelLimit = 4;
        $runtimes = [];
        for ($i = 0; $i < $parallelLimit; $i++) {
            $runtimes[] = new Runtime();
        }

        $tasks = [];
        $queue = [];

        // مرحله 1: بررسی متن مستقیم
        for ($page = 1; $page <= $totalPages; $page++) {
            $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
            if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
                $pagesWithKeyword[] = $page;
            } else {
                $queue[] = $page; // OCR لازم دارد
            }
        }

        // مرحله 2: اجرای OCR به صورت موازی
        $futureResults = [];
        foreach ($queue as $index => $page) {
            $runtime = $runtimes[$index % $parallelLimit];
            $futureResults[] = $runtime->run(function ($page, $filePath, $pdftoppm, $keyword) {
                $imagePath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__ . "/storage/files/_$page");
                $cmd = $pdftoppm . " -f $page -l $page -singlefile -png "
                    . escapeshellarg($filePath) . " "
                    . escapeshellarg($imagePath) . " 2>&1";
                exec($cmd, $output, $return_var);
                if ($return_var !== 0 || !file_exists($imagePath . ".png")) {
                    return null;
                }
                $ocrText = (new TesseractOCR($imagePath . ".png"))
                    ->lang('fas')
                    ->run();
                unlink($imagePath . ".png");
                if (mb_stripos($ocrText, $keyword) !== false) {
                    return $page;
                }
                return null;
            }, [$page, $filePath, $pdftoppm, $keyword]);
        }

        // جمع‌آوری نتایج OCR
        foreach ($futureResults as $future) {
            $res = $future->value();
            if ($res !== null) {
                $pagesWithKeyword[] = $res;
            }
        }

        sort($pagesWithKeyword);

        return response()->json([
            'keyword' => $keyword,
            'pages' => $pagesWithKeyword
        ]);
    }
    public function twostate()
    {
        $keyword = "قاسم";
        $filePath = str_replace('/', DIRECTORY_SEPARATOR, public_path('storage/files/test.pdf'));
        $pdftoppm = '"C:\\poppler-25.07.0\\Library\\bin\\pdftoppm.exe"';
        $pdftotext = '"C:\\poppler-25.07.0\\Library\\bin\\pdftotext.exe"';
        $pagesWithKeyword = [];

        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $totalPages = count($pdf->getPages());

        // OCR یک صفحه (تابع مشترک)
        $ocrFunction = function ($page, $filePath, $pdftoppm, $keyword) {
            $imagePath = str_replace('/', DIRECTORY_SEPARATOR, public_path("storage/files/_$page"));
            $cmd = $pdftoppm . " -f $page -l $page -singlefile -png "
                . escapeshellarg($filePath) . " "
                . escapeshellarg($imagePath) . " 2>&1";
            exec($cmd, $output, $return_var);

            if ($return_var !== 0 || !file_exists($imagePath . ".png")) {
                return null;
            }

            $ocrText = (new TesseractOCR($imagePath . ".png"))
                ->lang('fas')
                ->run();

            unlink($imagePath . ".png");

            if (mb_stripos($ocrText, $keyword) !== false) {
                return $page;
            }
            return null;
        };

        // مرحله 1: بررسی متن مستقیم
        $ocrQueue = [];
        for ($page = 1; $page <= $totalPages; $page++) {
            $text = shell_exec($pdftotext . ' -f ' . $page . ' -l ' . $page . ' -layout -q ' . escapeshellarg($filePath) . ' -');
            if (!empty(trim($text)) && mb_stripos($text, $keyword) !== false) {
                $pagesWithKeyword[] = $page;
            } else {
                $ocrQueue[] = $page; // نیاز به OCR
            }
        }

        // مرحله 2: OCR → موازی یا عادی
        if (extension_loaded('parallel')) {
            // 🔹 حالت موازی
            $parallelLimit = 4; // تعداد پروسس همزمان
            $runtimes = [];
            for ($i = 0; $i < $parallelLimit; $i++) {
                $runtimes[] = new \parallel\Runtime();
            }

            $futureResults = [];
            foreach ($ocrQueue as $index => $page) {
                $runtime = $runtimes[$index % $parallelLimit];
                $futureResults[] = $runtime->run($ocrFunction, [$page, $filePath, $pdftoppm, $keyword]);
            }

            foreach ($futureResults as $future) {
                $res = $future->value();
                if ($res !== null) {
                    $pagesWithKeyword[] = $res;
                }
            }
        } else {
            // 🔹 حالت عادی (تک‌نخی)
            foreach ($ocrQueue as $page) {
                $res = $ocrFunction($page, $filePath, $pdftoppm, $keyword);
                if ($res !== null) {
                    $pagesWithKeyword[] = $res;
                }
            }
        }

        sort($pagesWithKeyword);

        return response()->json([
            'keyword' => $keyword,
            'pages' => $pagesWithKeyword
        ]);
    }
}

