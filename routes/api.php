<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NewsPreferenceController;

Route::get('/news', [NewsController::class, 'index']);
Route::get('/preferences', [NewsPreferenceController::class, 'show']);
Route::put('/preferences', [NewsPreferenceController::class, 'upsert']);
