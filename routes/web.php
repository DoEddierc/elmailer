<?php

use App\Http\Controllers\CircularController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/circulares/enviar', [CircularController::class, 'send'])
    ->withoutMiddleware([VerifyCsrfToken::class]);

Route::get('/circulares/status/{id}', [CircularController::class, 'status'])
    ->withoutMiddleware([VerifyCsrfToken::class]);