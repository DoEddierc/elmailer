<?php

use App\Http\Controllers\CircularController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/circulares/enviar', [CircularController::class, 'send']);
Route::get('/circulares/status/{id}', [CircularController::class, 'status']);