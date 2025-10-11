<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
class CollectOcrPagesResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $filePath;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($files, $keyword)
    {
        $this->files = $files;
        $this->keyword = $keyword;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $results = [];

        foreach ($this->files as $file) {
            $filePath = public_path('storage/files/processes' . $file->path);

            $ocrKey   = 'ocr_pages_'   . md5($filePath); // OCR صفحات تصویری
            $textKey  = 'text_pages_'  . md5($filePath); // صفحات متنی معمولی

            // ---- خواندن صفحات OCR ----
            if (config('cache.default') === 'redis') {
                $ocrPages = Redis::smembers($ocrKey);
                Redis::del($ocrKey);
            } else {
                $ocrPages = Cache::get($ocrKey, []);
                Cache::forget($ocrKey);
            }

        
    }
}
