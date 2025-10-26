<?php

use App\Http\Controllers\CircularController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/redis-debug', function () {
    try {
        // Intenta crear cliente Redis con configuración actual
        $client = Redis::connection();
        $info = $client->clientList();
        Log::info('✅ Redis connection working. Client list:', [$info]);
        return 'Redis connection OK (mira los logs).';
    } catch (\Exception $e) {
        Log::error('❌ Redis connection failed: '.$e->getMessage());
        return 'Redis failed: '.$e->getMessage();
    }
});



Route::get('/queue-info', function () {
    try {
        $pending = Redis::connection()->llen('queues:default');
        $delayed = Redis::connection()->zcard('queues:default:delayed');
        return ['queue' => 'default', 'pending' => $pending, 'delayed' => $delayed];
    } catch (\Throwable $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->withoutMiddleware([VerifyCsrfToken::class]);

Route::post('/queue-test', function () {
    \App\Jobs\TestLogJob::dispatch(); // crea este Job abajo
    return ['ok' => true];
})->withoutMiddleware([VerifyCsrfToken::class]);



Route::post('/circulares/enviar', [CircularController::class, 'send'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/circulares/status/{id}', [CircularController::class, 'status'])
    ->withoutMiddleware([VerifyCsrfToken::class]);