<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Exception Handler
 *
 * Handles different types of exceptions and converts them to standardized JSON responses
 *
 * @package App\Exceptions
 */
class HandlerException
{
    /**
     * Handle exception and return standardized JSON response
     *
     * @param Throwable $e The exception to handle
     * @return JsonResponse Standardized JSON error response
     */
    public function handle(Throwable $e): JsonResponse
    {
        $statusCode = 500;
        $message = 'Server Error';
        $errors = [];

        switch (true) {
            case $e instanceof ValidationException:
                $statusCode = 422;
                $message = 'Validation Error';
                $errors = $e->errors();
                break;

            case $e instanceof AuthenticationException:
                $statusCode = 401;
                $message = 'Unauthenticated';
                break;

            case $e instanceof HttpException:
                $statusCode = $e->getStatusCode();
                $message = $e->getMessage();
                break;

            default:

        }

        $response = [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null
        ];

        if (config('app.debug')) {
            $response['debug'] = [
                'exception' => get_class($e),
                'detail' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(10)->toArray()
            ];
        }

        return new JsonResponse($response, $statusCode);
    }
}
