<?php

namespace App\Exceptions;

use App\Services\FailureAlertService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, Request $request) {
            $routeName = $request->route()?->getName();
            $path = trim($request->path(), '/');

            $flow = match (true) {
                $routeName === 'api.orders.store',
                $path === 'api/orders' => 'order',
                $routeName === 'api.reservations.store',
                $path === 'api/reservations' => 'reservation',
                default => null,
            };

            if ($flow === null || $request->attributes->get('failure_alert_sent')) {
                return null;
            }

            $details = [];

            if ($e instanceof ValidationException) {
                $details['validation_errors'] = $e->errors();
            }

            app(FailureAlertService::class)->notify($flow, $request, [
                'error_type' => $e instanceof ValidationException ? 'validation_failure' : 'unhandled_exception',
                'message' => $e->getMessage(),
                'status' => $e instanceof ValidationException ? 422 : 500,
                'details' => $details,
            ], $e);

            return null;
        });
    }
}
