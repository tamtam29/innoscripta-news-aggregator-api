<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\SourceController;
use App\Http\Controllers\Api\PreferenceController;

// News endpoints
Route::get('/news/headlines', [NewsController::class, 'headlines']);   // Get top headlines
Route::get('/news/search', [NewsController::class, 'search']);      // Search articles with keyword
Route::get('/news/{id}', [NewsController::class, 'show']);        // Show specific article
Route::delete('/news/{id}', [NewsController::class, 'destroy']);     // Delete specific article

// Source endpoints
Route::get('/sources', [SourceController::class, 'index']);      // Get all sources

// Preference endpoints
Route::get('/preferences', [PreferenceController::class, 'index']);           // Get all preferences
Route::put('/preferences', [PreferenceController::class, 'update']);          // Update preferences
