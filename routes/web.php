<?php

use App\Http\Controllers\Api\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Explicit storage and media delivery routes with CORS headers (for Flutter Web and Mobile)
Route::get('/storage/{path}', [MediaController::class, 'serveFile'])->where('path', '.*');
Route::get('/media/file/{path}', [MediaController::class, 'serveFile'])->where('path', '.*');
