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


Route::post('/circulares/enviar', [CircularController::class, 'send'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/circulares/status/{id}', [CircularController::class, 'status'])
    ->withoutMiddleware([VerifyCsrfToken::class]);