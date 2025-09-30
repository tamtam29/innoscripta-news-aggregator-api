<?php

namespace App\Http\Controllers;

/**
 * Base Controller
 *
 * @package App\Http\Controllers
 *
 * @OA\Info(
 *     title="News Aggregator API",
 *     version="1.0.0",
 *     description="A comprehensive news aggregation API that fetches articles from multiple sources including NewsAPI, The Guardian, and The New York Times. Provides endpoints for retrieving headlines, searching articles, and managing news preferences.",
 *     @OA\Contact(
 *         name="API Support",
 *         email="aditiatama@gmail.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="/api",
 *     description="News Aggregator API Server"
 * )
 */
abstract class Controller
{
    //
}
