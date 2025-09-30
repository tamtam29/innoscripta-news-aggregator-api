<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NewsPreferenceController;

// News endpoints
Route::get('/news/headlines', [NewsController::class, 'headlines']); // Get top headlines
Route::get('/news/search',    [NewsController::class, 'search']);    // Search articles with keyword

// User preferences endpoints  
Route::get('/preferences', [NewsPreferenceController::class, 'show']);   // Get user preferences
Route::put('/preferences', [NewsPreferenceController::class, 'upsert']); // Update user preferences
