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
use App\Http\Resources\ProcessFileSearchResult;
use Illuminate\Pagination\LengthAwarePaginator;
class CollectOcrPagesResultsJob3 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // protected $files;
    protected $filesData;
    protected $pagesWithKeyword;
    protected $textPositions;

    protected $keyword;
    protected $searchId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filesData, $keyword, $searchId, $pagesWithKeyword)
    {
        $this->pagesWithKeyword = $pagesWithKeyword;
        $this->filesData = $filesData;
        $this->keyword = $keyword;
        $this->searchId = $searchId;
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
        foreach ($this->filesData as $file) {
            
            $fileName = $file['file_name'];
            $filePath = $file['file_path'];
            $docName = $file['doc_name'];
            $architectureName = $file['architecture_name'];
            $code = $file['code'];
            // $filePath = public_path('storage/files/processes/' . $file->filePath);

            $ocrKey = 'ocr_pages_' . md5($filePath); // OCR صفحات تصویری
            $textKey = 'text_pages_' . md5($filePath); // صفحات متنی معمولی
            $ocrPositionKey = 'ocr_positions_' . md5($filePath);
            $textPositionKey = 'text_positions_' . md5($filePath);
            $ocrPages = Cache::get($ocrKey, []);
            $ocrPositions = Cache::get($ocrPositionKey, []);
            Log::info('✅ocrPages:  ', $ocrPages);
            Cache::forget($ocrKey);
            Cache::forget($ocrPositionKey);
            // if (config('cache.default') === 'redis') {
            //     $ocrPages = Redis::smembers($ocrKey);
            //     $ocrPositions = Redis::get($ocrPositionKey) ? json_decode(Redis::get($ocrPositionKey), true) : [];
            //     Redis::del([$ocrKey, $ocrPositionKey]);
            // } else {
            //     $ocrPages = Cache::get($ocrKey, []);
            //     $ocrPositions = Cache::get($ocrPositionKey, []);
            //     Log::info('✅ocrPages:  ', $ocrPages);
            //     Cache::forget($ocrKey);
            //     Cache::forget($ocrPositionKey);
            // }



            // ---- خواندن صفحات متنی ----
            $textPages = Cache::get($textKey, []);
            // $textPages = $this->pagesWithKeyword;
            Log::info('✅textPages:  ', $textPages);
            // $textPositions = Cache::get($textPositionKey, []);
            Cache::forget($textKey);
            // Cache::forget($textPositionKey);

            // $allPositions = [];
            // foreach ($textPages as $page => $positions) {
            //     foreach ($positions as $pos) {
            //         $pos['type'] = 'text';
            //         $allPositions[] = $pos;
            //     }
            // }
            // foreach ($ocrPositions as $page => $positions) {
            //     foreach ($positions as $pos) {
            //         $pos['type'] = 'ocr';
            //         $allPositions[] = $pos;
            //     }
            // }
            // usort($allPositions, function ($a, $b) {
            //     if ($a['page'] === $b['page']) {
            //         return $a['position'] - $b['position'];
            //     }
            //     return $a['page'] - $b['page'];
            // });
            if (count($textPages) || count($ocrPages)) {
                Log::info('✅این فایل کلمه را دارد*************** ');
                Log::info(array_map('intval', $textPages));
                Log::info(array_map('intval', $ocrPages));
                $results[] = [
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'doc_name' => $docName ?? null,
                    'code' => $code ?? null,
                    'architecture_name' => $architectureName ?? null,
                    'found_in_text' => array_map('intval', $textPages),
                    'found_in_images' => array_map('intval', $ocrPages),
                    // 'positions' => $allPositions, // تمام موقعیت‌های دقیق
                    // 'total_matches' => count($allPositions)
                ];
            }
        }
        Cache::put("ocr_result_{$this->searchId}", $results, 3600);
    

        event(new OcrCompleted([
            'search_id' => $this->searchId,
            'status' => 'completed'
        ]));

    }
}
