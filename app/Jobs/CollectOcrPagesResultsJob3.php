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
    protected $fileData;
    protected $pagesWithKeyword;
    protected $textPositions;

    protected $keyword;
    protected $searchId;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileData, $keyword, $searchId, $pagesWithKeyword, $textPositions)
    {
        $this->pagesWithKeyword = $pagesWithKeyword;
        $this->textPositions = $textPositions;
        $this->fileData = $fileData;
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
        info('📢 شروع جمع کردن نتایج');
        foreach ($this->fileData as $file) {
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
            Log::info("GET KEY: $textKey", ['path' => $filePath]);
            Log::info('✅textPages:  ', $textPages);
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
            usort($allPositions, function ($a, $b) {
                if ($a['page'] === $b['page']) {
                    return $a['position'] - $b['position'];
                }
                return $a['page'] - $b['page'];
            });
            if (count($textPages) || count($ocrPages)) {
                $results[] = [
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'doc_name' => $docName ?? null,
                    'code' => $code ?? null,
                    'architecture_name' => $architectureName ?? null,
                    'found_in_text' => array_map('intval', $textPages),
                    'found_in_images' => array_map('intval', $ocrPages),
                    'positions' => $allPositions, // تمام موقعیت‌های دقیق
                    'total_matches' => count($allPositions)
                ];
            }
        }
        $perPage = 10;
        $page = 1;

        $collection = collect($results)->map(fn($item) => (object) $item);

        $total = $collection->count();

        $paginated = new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => '', 'query' => []]
        );
        $resource = ProcessFileSearchResult::collection($paginated)->response()->getData(true);

        $responseData = [
            "searchId" => $this->searchId,
            "keyword" => $this->keyword,
            "typeDoc" => "فرایند",
            "status" => 'کامل',
            "files" => $resource['data'],
            "links" => $resource['links'],
            "meta" => $resource['meta']
        ];


        // 🚀 ارسال به فرانت
        // broadcast(new SearchCompletedEvent($responseData));
        // $finalKey = 'ocr_final_result_' . md5($this->keyword);
        // Cache::put($finalKey, $results, now()->addMinutes(60));

        // info('📢 OCR Results before event:jadid');

        event(new OcrCompleted($responseData));

    }
}
