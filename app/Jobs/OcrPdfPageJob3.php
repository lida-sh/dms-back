<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class OcrPdfPageJob3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected $page;
    protected $filePath;
    protected $pdftoppm;
    protected $keyword;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($page, $filePath, $pdftoppm, $keyword)
    {
        $this->page = $page;
        $this->filePath = $filePath;
        $this->pdftoppm = $pdftoppm;
        $this->keyword = $keyword;
        $this->onConnection('database');
        $this->onQueue('ocr');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("▶️ Running OCR job for page {$this->page} (Batch: " . optional($this->batch())->id . ")");
        Log::info("DEBUG BEFORE TRY", [
            'page' => $this->page,
            'filePath' => $this->filePath,
            'pdftoppm' => $this->pdftoppm,
            'keyword' => $this->keyword,
        ]);
        try {
            $tesseractPath = 'C:\Users\0532350669\AppData\Local\Programs\Tesseract-OCR\tesseract.exe';
            $filePathComplete = public_path('storage/files/' . 'processes' . '/' . $this->filePath);
            $pdfPath = str_replace('/', '\\', $filePathComplete);
            Log::info("بدنه داخل جاب");
            $key = 'ocr_pages_' . md5($this->filePath);
            $imagePath = str_replace('/', DIRECTORY_SEPARATOR, public_path("storage/files/_$this->page"));
            Log::info("imagePath: " . $imagePath);
            // $cmd = $this->pdftoppm . ' -png -f ' . $this->page . ' -l ' . $this->page . ' -r 200 ' .
            //     escapeshellarg($filePathComplete) . ' ' . $imagePath;
            $gs = "C:\\Program Files\\gs\\gs10.06.0\\bin\\gswin64c.exe";
            $outputPath = $imagePath . ".png";
            // $cmd = "\"$gs\" -dNOPAUSE -dBATCH -dSAFER -sDEVICE=png16m -r300 " .
            //     "-sOutputFile=" . escapeshellarg($outputPath) . " " .
            //     escapeshellarg($pdfPath);

            $cmd = "\"$gs\" -dNOPAUSE -dBATCH -dSAFER -sDEVICE=png16m -r300 "
                . "-dFirstPage={$this->page} -dLastPage={$this->page} "
                . "-sOutputFile=" . escapeshellarg($outputPath) . " "
                . escapeshellarg($pdfPath);
            exec($cmd, $output, $return);
            // Log::info("GS Output:", [
            //     'cmd' => $cmd,
            //     'return' => $return,
            //     'output' => $output,
            //     'exists' => file_exists($outputPath)
            // ]);
            //-----------------------------
            // $cmd = $this->pdftoppm آخرین کامند
            //     . " -f {$this->page} -l {$this->page} -singlefile -png "
            //     . "--fontdir " . escapeshellarg("C:\\Windows\\Fonts") . " "
            //     . escapeshellarg($filePathComplete) . " "
            //     . escapeshellarg($imagePath)
            //     . " 2>&1";
            //-----------------------------
            // $cmd = $this->pdftoppm . " -f {$this->page} -l {$this->page} -singlefile -png "
            //     . escapeshellarg($filePathComplete) . " "
            //     . escapeshellarg($imagePath) . " 2>&1";
            //----------------
            // exec($cmd, $output, $return_var);
            // if ($return_var !== 0 || !file_exists($imagePath . ".png")) {
            //     return; // خطا → صفحه رد میشه
            // }
            // Log::info("Checking exists: " . ($imagePath . ".png"), [
            //     'exists' => file_exists($imagePath . ".png")
            // ]);
            // Log::info("EXEC OUTPUT", [
            //     'return_var' => $return_var,
            //     'output' => $output
            // ]);
            //-----------------------------
            $ocr = new TesseractOCR($imagePath . '.png');
            $ocr->executable($tesseractPath);
            $ocr->lang('fas')->psm(6)->oem(1);
           
            $ocrText = $ocr->run();
            // $ocrText = (new TesseractOCR($imagePath . ".png"))
            //     ->lang('fas')
            //     ->psm(6)
            //     ->oem(1)
            //     ->run();
            Log::info('ocrText......' . $ocrText);
            unlink($imagePath . ".png");
            // Log::info('ocrTest' . mb_stripos($ocrText, $this->keyword));
            if (!empty($ocrText) && mb_stripos($ocrText, $this->keyword) !== false) {
                $ocrPositions = $this->findOcrKeywordPositions($ocrText, $this->keyword, $this->page);
                Log::info('ocrPositions کلمه دیده شد');
                
                $key = 'ocr_pages_' . md5($this->filePath);
                $positionKey = 'ocr_positions_' . md5($this->filePath);
                // Redis::sadd($key, $this->page);            // add page to set
                // Redis::expire($key, 60 * 60);              // expire 1 hour
                $pages = Cache::get($key, []);
                if (!in_array($this->page, $pages)) {
                    $pages[] = $this->page;
                    Cache::put($key, $pages, now()->addHours(1));
                }
                $existingPositions = Cache::get($positionKey, []);
                $existingPositions[$this->page] = $ocrPositions;
                Cache::put($positionKey, $existingPositions, now()->addHours(1));

                // ذخیره در Redis یا DB
                // \Cache::connection('redis')->sadd("pdf:ocr:results", $this->page);
                //     OcrTempResult::create([
                //     'file_id' => $this->fileId,
                //     'page_number' => $this->page,

                // ]);
            }

        } catch (\Throwable $e) {
            Log::error("OcrPdfPageJob failed for {$this->filePath} page {$this->page}: " . $e->getMessage());
        }
    }
    private function findOcrKeywordPositions($text, $keyword, $page)
    {
        $positions = [];
        $offset = 0;
        $keyword = mb_strtolower($keyword);
        $textLower = mb_strtolower($text);

        while (($pos = mb_stripos($textLower, $keyword, $offset)) !== false) {
            // برای OCR، موقعیت‌ها نسبی هستند
            $textBefore = mb_substr($text, 0, $pos);
            $linesBefore = explode("\n", $textBefore);
            $lineNumber = count($linesBefore);
            $column = mb_strlen(end($linesBefore)) + 1;

            $startContext = max(0, $pos - 30);
            $endContext = min(mb_strlen($text), $pos + mb_strlen($keyword) + 30);
            $context = mb_substr($text, $startContext, $endContext - $startContext);

            $positions[] = [
                'page' => $page,
                'position' => $pos,
                'line' => $lineNumber,
                'column' => $column,
                'context' => $context,
                'length' => mb_strlen($keyword),
                'type' => 'ocr' // برای تشخیص منبع در فرانت
            ];

            $offset = $pos + mb_strlen($keyword);
        }
        return $positions;
    }
}
