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
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $key = "ocr_result_" . md5($this->filePath);
        $pages = Cache::get($key, []);

        // اینجا می‌تونی مثلا جمع عددی صفحات یا لیست صفحات را در DB ذخیره کنی
        $sum = array_sum($pages);

        // نمونه: ذخیره در جدول results
        \DB::table('ocr_results')->insert([
            'file_path' => $this->filePath,
            'pages_sum' => $sum,
            'pages' => json_encode($pages),
            'created_at' => now(),
        ]);

        // حذف cache موقت
        Cache::forget($key);
        
    }
}
