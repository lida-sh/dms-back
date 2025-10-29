<?php
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Config;

function queueConnection(): string
{
    return 'database';
    // try {
    //     Redis::connection()->ping();
    //     return 'redis';
    // } catch (\Throwable $e) {
    //     // اگر Redis در دسترس نبود
    //     return 'database';
    // }
}