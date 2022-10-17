<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e): JsonResponse|HttpFoundationResponse
    {
        if(config('app.debug')) {
            return parent::render($request, $e);
        }

        return match (true) {
            $e instanceof ModelNotFoundException => $this->modelNotFound(),
            $e instanceof NotFoundHttpException => $this->routeNotFound($e),
            $e instanceof ApiHotelValidationException => $this->validationError($e),
            $e instanceof ApiException => $this->badApiRequest($e),
            default => parent::render($request, $e),
        };
    }

    private function badApiRequest(Throwable $e): JsonResponse
    {
        // TODO log this
        return response()->json(['error' => $e->getMessage()], $e->getCode());
    }

    private function modelNotFound(): JsonResponse
    {
        return response()->json(['error' => 'Record not found.'], HttpFoundationResponse::HTTP_NOT_FOUND);
    }

    private function routeNotFound(): JsonResponse
    {
        return response()->json(['error' => 'Not found.'], HttpFoundationResponse::HTTP_NOT_FOUND);
    }

    private function validationError(ApiHotelValidationException $e): JsonResponse
    {
        return response()->json([
            'message' => $e->getMessage(),
            'errors' => $e->getErrors(),
        ], $e->getCode());
    }
}
