<?php

namespace App\Exceptions;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\appLogModel;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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

    public function render($request, Throwable $exception)
    {
        $uuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'processId');
        $Muuid = AppHelper::Instance()->generateUniqueUuid(appLogModel::class, 'groupId');

        appLogModel::create([
            'processId' => $uuid,
            'groupId' => $Muuid,
            'errReason' => null,
            'errStatus' => null
        ]);

        try {
            $currentRoute = $request->route();
            $currentRouteInfo = $currentRoute ? $currentRoute->uri() : 'No route information available';
            Log::info($currentRouteInfo);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve current route: ' . $e->getMessage());
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            $uri = $request->path();
            $route = $request->route();
            if ($route && method_exists($route, 'methods')) {
                $methods = $route->methods();
                $message = 'MethodNotAllowedHttpException for route: ' . $uri . ' (' . implode(', ', $methods) . ')';
                Log::error($message);
                appLogModel::where('groupId', '=', $Muuid)
                    ->update([
                        'errReason' => 'Method not allowed exception!',
                        'errStatus' => $message,
                    ]);
            } else {
                $message = 'MethodNotAllowedHttpException for route: ' . $uri;
                appLogModel::where('groupId', '=', $Muuid)
                    ->update([
                        'errReason' => 'Method not allowed exception!',
                        'errStatus' => $message,
                    ]);
                Log::error($message);
            }
            abort(403);
        } else if ($exception instanceof TokenMismatchException || ($exception instanceof HttpException && $exception->getStatusCode() == 419)) {
             $message = 'TokenMismatchException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => 'TokenMismatchException',
                    'errStatus' => $message,
                ]);
            abort(401);
        } else if ($exception instanceof RouteNotFoundException) {
            $message = 'RouteNotFoundException: ' . $exception->getMessage();
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => 'Route not found exception!',
                    'errStatus' => $message,
                ]);
            return response()->json([
                'status' => 404,
                'message' => 'Route not found exception!',
            ], 404);
        } else if ($exception instanceof NotFoundHttpException || ($exception instanceof HttpException && $exception->getStatusCode() == 404)) {
            $message = 'NotFoundHttpException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '404 - Page not found',
                    'errStatus' => $message,
                ]);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 403) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '403 - Forbidden',
                    'errStatus' => $message,
                ]);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 419) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '419 - Page Expired',
                    'errStatus' => $message
                ]);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 429) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '429 - Too Many Requests',
                    'errStatus' => $message
                ]);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 500) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '500 - Internal Server Error',
                    'errStatus' => $message
                ]);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 503) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            Log::error($message);
            appLogModel::where('groupId', '=', $Muuid)
                ->update([
                    'errReason' => '503 - Service Temporary Unavailable',
                    'errStatus' => $message
                ]);
        }
        return parent::render($request, $exception);
    }
}
