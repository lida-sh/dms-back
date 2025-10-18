<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\PdfSearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Bus\Batchable;
class SearchPdfFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;
    protected $file;
    protected $keyword;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($file, $keyword)
    {
        $this->file = $file;
        $this->keyword = $keyword;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $service = new PdfSearchService();
        $filePath = public_path('storage/'. $this->file->filePath);

        if (!file_exists($filePath)) {
            Log::warning("File not found: {$filePath}");
            return;
        }

        $result = $service->searchPdf($filePath, $this->keyword);

        // ذخیره نتیجه موقت در Cache
        $key = "search_result_" . md5($filePath);
        Cache::put($key, [
            'file_name' => $this->file->file_name ?? $this->file->name ?? basename($filePath),
            'process_name' => $this->file->process_name ?? ($this->file->process->name ?? null),
            'result' => $result,
        ], now()->addMinutes(30));
    
    }
}
