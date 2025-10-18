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
class OcrPdfPageJob implements ShouldQueue
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = 'ocr_pages_' . md5($this->filePath);
        $imagePath = str_replace('/', DIRECTORY_SEPARATOR, public_path("storage/files/_$this->page"));

        $cmd = $this->pdftoppm . " -f {$this->page} -l {$this->page} -singlefile -png "
            . escapeshellarg($this->filePath) . " "
            . escapeshellarg($imagePath) . " 2>&1";

        exec($cmd, $output, $return_var);
        if ($return_var !== 0 || !file_exists($imagePath . ".png")) {
            return; // خطا → صفحه رد میشه
        }

        $ocrText = (new TesseractOCR($imagePath . ".png"))
            ->lang('fas')
            ->run();

        unlink($imagePath . ".png");

        if (mb_stripos($ocrText, $this->keyword) !== false) {
            $pages = Cache::get($key, []);
            $pages[] = $this->page;

            Cache::put($key, $pages, now()->addHours(1));
            // ذخیره در Redis یا DB
            // \Cache::connection('redis')->sadd("pdf:ocr:results", $this->page);
            //     OcrTempResult::create([
            //     'file_id' => $this->fileId,
            //     'page_number' => $this->page,

            // ]);
        }

    }
}
