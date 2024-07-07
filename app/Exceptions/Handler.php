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
                Log::error($message);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Method not allowed exception!',
                        'errStatus' => $message
                    ]);
                    DB::table('accessLogs')->insert([
                        'processId' => $uuid,
                        'routePath' => $currentRouteInfo,
                        'accessIpAddress' => $userIp
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $e->getMessage());
                }
                return response()->json([
                    'status' => 401,
                    'message' => '401 - Request method not allowed',
                    'response' => $message,
                ], 401);
            } else {
                $message = 'MethodNotAllowedHttpException for route: ' . $uri;
                Log::error($message);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Method not allowed exception!',
                        'errStatus' => $message
                    ]);
                    DB::table('accessLogs')->insert([
                        'processId' => $uuid,
                        'routePath' => null,
                        'accessIpAddress' => $userIp
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $e->getMessage());
                }
                return response()->json([
                    'status' => 401,
                    'message' => '401 - Request method not allowed',
                    'response' => $message
                ], 401);
            }
        } else if ($exception instanceof TokenMismatchException || ($exception instanceof HttpException && $exception->getStatusCode() == 419)) {
             $message = 'TokenMismatchException: ' . $exception->getMessage();
             try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => 'Token Mismatch exception!',
                    'errStatus' => $message
                ]);
                DB::table('accessLogs')->insert([
                    'processId' => $uuid,
                    'routePath' => $currentRouteInfo,
                    'accessIpAddress' => $userIp
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $e->getMessage());
            }
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Token Mismatch exception!', $currentRouteInfo, $message, $userIp);
            return response()->json([
                'status' => 401,
                'message' => '401 - Invalid token request',
                'response' => $message
            ], 401);
        } else if ($exception instanceof RouteNotFoundException) {
            $message = 'RouteNotFoundException: ' . $exception->getMessage();
             try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => 'Route not found exception!',
                    'errStatus' => $message
                ]);
                DB::table('accessLogs')->insert([
                    'processId' => $uuid,
                    'routePath' => $currentRouteInfo,
                    'accessIpAddress' => true
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $e->getMessage());
            }
            NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', 'Route not found exception!', $currentRouteInfo, $message, $userIp);
            return response()->json([
                'status' => 404,
                'message' => '404 - Route not found',
                'response' => $message
            ], 404);
        } else if ($exception instanceof NotFoundHttpException || ($exception instanceof HttpException && $exception->getStatusCode() == 404)) {
            $message = 'NotFoundHttpException: ' . $exception->getMessage();
            Log::error($message);
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '404 - Page not found',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           return response()->json([
               'status' => 404,
               'message' => '404 - Page not found',
               'response' => $message
           ], 404);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 403) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '403 - Forbidden',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '403 - Forbidden', $currentRouteInfo, $message, $userIp);
           return response()->json([
               'status' => 403,
               'message' => '403 - Forbidden',
               'response' => $message
           ], 403);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 419) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '419 - Page Expired',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '419 - Page Expired', $currentRouteInfo, $message, $userIp);
           return response()->json([
               'status' => 419,
               'message' => '419 - Page Expired',
               'response' => $message
           ], 419);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 429) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '429 - Too Many Requests',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '429 - Too Many Requests', $currentRouteInfo, $message, $userIp);
           return response()->json([
               'status' => 429,
               'message' => '429 - Too Many Requests',
               'response' => $message
           ], 429);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 500) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '500 - Internal Server Error',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '500 - Internal Server Error', $currentRouteInfo, $message, $userIp);
           return response()->json([
               'status' => 500,
               'message' => '500 - Internal Server Error',
               'response' => $message
           ], 500);
        } else if ($exception instanceof HttpException && $exception->getStatusCode() == 503) {
            $message = 'HTTPResponseException: ' . $exception->getMessage();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => '503 - Service Temporary Unavailable',
                    'errStatus' => $message
                ]);
               DB::table('accessLogs')->insert([
                   'processId' => $uuid,
                   'routePath' => $currentRouteInfo,
                   'accessIpAddress' => $userIp
               ]);
           } catch (QueryException $ex) {
               Log::error('Query Exception failed with: '. $e->getMessage());
           }
           NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '503 - Service Temporary Unavailable', $currentRouteInfo, $message, $userIp);
           return response()->json([
               'status' => 503,
               'message' => '502 - Service Temporary Unavailable',
               'response' => $message
           ], 503);
        }

        return parent::render($request, $exception);
    }
}
