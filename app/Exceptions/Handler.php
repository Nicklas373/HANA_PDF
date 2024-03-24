<?php

namespace App\Exceptions;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
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
        $uuid = AppHelper::Instance()->get_guid();
        $userIp = $request->ip();

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
                NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Method not allowed exception!', $currentRouteInfo, $message, $userIp);
                try {
                    DB::table('accessLogs')->insert([
                        'processId' => $uuid,
                        'routePath' => $currentRouteInfo,
                        'accessIpAddress' => true,
                        'routeExceptionMessage' => 'Method not allowed exception!',
                        'routeExceptionLog' => $message
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $e->getMessage());
                }
            } else {
                $message = 'MethodNotAllowedHttpException for route: ' . $uri;
                NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Method not allowed exception!', 'null', $message, $userIp);
                try {
                    DB::table('accessLogs')->insert([
                        'processId' => $uuid,
                        'routePath' => null,
                        'accessIpAddress' => true,
                        'routeExceptionMessage' => 'Method not allowed exception!',
                        'routeExceptionLog' => $message
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $e->getMessage());
                }
            }
            abort(403);
        } else if ($exception instanceof TokenMismatchException || ($exception instanceof HttpException && $exception->getStatusCode() == 419)) {
             $message = 'TokenMismatchException: ' . $exception->getMessage();
             NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Token Mismatch exception!', $currentRouteInfo, $message, $userIp);
             try {
                DB::table('accessLogs')->insert([
                    'processId' => $uuid,
                    'routePath' => $currentRouteInfo,
                    'accessIpAddress' => true,
                    'routeExceptionMessage' => 'Token Mismatch exception!',
                    'routeExceptionLog' => $message
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $e->getMessage());
            }
            abort(401);
        }  else if ($exception instanceof RouteNotFoundException) {
            $message = 'RouteNotFoundException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Route not found exception!', $currentRouteInfo, $message, $userIp);
             try {
                DB::table('accessLogs')->insert([
                    'processId' => $uuid,
                    'routePath' => $currentRouteInfo,
                    'accessIpAddress' => true,
                    'routeExceptionMessage' => 'Route not found exception!',
                    'routeExceptionLog' => $message
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $e->getMessage());
            }
            return response()->json([
                'status' => 404,
                'message' => 'Route not found exception!',
            ], 404);
        } else if ($exception instanceof NotFoundHttpException || ($exception instanceof HttpException && $exception->getStatusCode() == 404)) {
            $message = 'NotFoundHttpException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '404 - Page not found', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '404 - Page not found',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 403) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '403 - Forbidden', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '403 - Forbidden',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 419) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '419 - Page Expired', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '419 - Page Expired',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 429) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '429 - Too Many Requests', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '429 - Too Many Requests',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 500) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '500 - Internal Server Error', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '500 - Internal Server Error',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 503) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '503 - Service Temporary Unavailable', $currentRouteInfo, $message, $userIp);
            try {
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => true,
                   'routeExceptionMessage' => '503 - Service Temporary Unavailable',
                   'routeExceptionLog' => $message
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
        }
        return parent::render($request, $exception);
    }
}
