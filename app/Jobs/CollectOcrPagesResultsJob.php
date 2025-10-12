<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class CollectOcrPagesResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $files;
    protected $keyword;
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
            $filePath = public_path('storage/files/processes/' . $file->filePath);

            $ocrKey = 'ocr_pages_' . md5($filePath); // OCR صفحات تصویری
            $textKey = 'text_pages_' . md5($filePath); // صفحات متنی معمولی
            if (config('cache.default') === 'redis') {
                $ocrPages = Redis::smembers($ocrKey);
                Redis::del($ocrKey);
            } else {
                $ocrPages = Cache::get($ocrKey, []);
                Cache::forget($ocrKey);
            }

            // ---- خواندن صفحات متنی ----
            $textPages = Cache::get($textKey, []);
            Cache::forget($textKey);
            $results[] = [
                'file_name' => $file->file_name,
                'process_name' => $file->process_name ?? null,
                'found_in_text' => array_map('intval', $textPages),
                'found_in_images' => array_map('intval', $ocrPages),
            ];
            $finalKey = 'ocr_final_result_' . md5($this->keyword);
            Cache::put($finalKey, $results, now()->addMinutes(60));

            Log::info('✅ CollectOcrPagesResultsJob completed for keyword: ' . $this->keyword);

        }
    }
}
