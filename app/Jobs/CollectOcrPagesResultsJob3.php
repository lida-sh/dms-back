<?php

namespace App\Jobs;
use App\Events\OcrCompleted;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
class CollectOcrPagesResultsJob3 implements ShouldQueue
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
        $results = [];

        foreach ($this->files as $file) {
            $filePath = public_path('storage/files/processes/' . $file->filePath);

            $ocrKey = 'ocr_pages_' . md5($filePath); // OCR صفحات تصویری
            $textKey = 'text_pages_' . md5($filePath); // صفحات متنی معمولی
            $ocrPositionKey = 'ocr_positions_' . md5($filePath);
            $textPositionKey = 'text_positions_' . md5($filePath);

            if (config('cache.default') === 'redis') {
                $ocrPages = Redis::smembers($ocrKey);
                $ocrPositions = Redis::get($ocrPositionKey) ? json_decode(Redis::get($ocrPositionKey), true) : [];
                Redis::del([$ocrKey, $ocrPositionKey]);
            } else {
                $ocrPages = Cache::get($ocrKey, []);
                $ocrPositions = Cache::get($ocrPositionKey, []);
                Cache::forget($ocrKey);
                Cache::forget($ocrPositionKey);
            }

            // ---- خواندن صفحات متنی ----
            $textPages = Cache::get($textKey, []);
            $textPositions = Cache::get($textPositionKey, []);
            Cache::forget($textKey);
            Cache::forget($textPositionKey);

            $allPositions = [];
            foreach ($textPositions as $page => $positions) {
                foreach ($positions as $pos) {
                    $pos['type'] = 'text';
                    $allPositions[] = $pos;
                }
            }
            foreach ($ocrPositions as $page => $positions) {
                foreach ($positions as $pos) {
                    $pos['type'] = 'ocr';
                    $allPositions[] = $pos;
                }
            }
            usort($allPositions, function($a, $b) {
                if ($a['page'] === $b['page']) {
                    return $a['position'] - $b['position'];
                }
                return $a['page'] - $b['page'];
            });
            $results[] = [
                'file_name' => $file->file_name,
                'file_path' => $file->filePath,
                'process_name' => $file->process->title ?? null,
                'found_in_text' => array_map('intval', $textPages),
                'found_in_images' => array_map('intval', $ocrPages),
                'positions' => $allPositions, // تمام موقعیت‌های دقیق
                'total_matches' => count($allPositions)
            ];
            

        }
        $finalKey = 'ocr_final_result_' . md5($this->keyword);
        Cache::put($finalKey, $results, now()->addMinutes(60));

        info('📢 OCR Results before event:hadid', $results);
        
        event(new OcrCompleted($this->keyword, $results));

    }
}
