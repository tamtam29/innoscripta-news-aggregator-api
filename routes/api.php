<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\NewsPreferenceController;

// News endpoints
Route::get('/news/headlines', [NewsController::class, 'headlines']); // Get top headlines
Route::get('/news/search',    [NewsController::class, 'search']);    // Search articles with keyword
Route::get('/news/{id}',      [NewsController::class, 'show']);      // Show specific article
Route::delete('/news/{id}',   [NewsController::class, 'destroy']);   // Delete specific article

// User preferences endpoints  
Route::get('/preferences', [NewsPreferenceController::class, 'show']);   // Get user preferences
Route::put('/preferences', [NewsPreferenceController::class, 'upsert']); // Update user preferences
