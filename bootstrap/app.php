<?php

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Models\appLogModel;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/up');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle AuthenticationException
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->is('up')) {
                $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');
                $isAjax = $request->ajax();
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $ex) {
                    $message = 'JWTAuth - TokenExpiredException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenExpiredException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenExpiredException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $ex) {
                    $message = 'JWTAuth - TokenInvalidException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenInvalidException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenInvalidException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $ex) {
                    $message = 'JWTAuth - JWTException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - JWTException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - JWTException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                }

                $message = 'AuthenticationException: ' . $e->getMessage();
                Log::error($message);

                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => 'Authentication Exception',
                        'errStatus' => $message
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $ex->getMessage());
                }
                NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '401 - Authentication Exception', $message);
                return response()->json([
                    'status' => 401,
                    'message' => '401 - Authentication Exception',
                    'info' => $message,
                ], 401);
            }
        });

        // Handle NotFoundHttpException
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('up')) {
                $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');
                $isAjax = $request->ajax();
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $ex) {
                    $message = 'JWTAuth - TokenExpiredException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenExpiredException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenExpiredException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $ex) {
                    $message = 'JWTAuth - TokenInvalidException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenInvalidException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenInvalidException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $ex) {
                    $message = 'JWTAuth - JWTException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - JWTException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - JWTException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                }

                $message = 'NotFoundHttpException: ' . $e->getMessage();
                Log::error($message);
                return response()->json([
                    'status' => 404,
                    'message' => '404 - Page not found',
                    'info' => $message
                ], 404);
            }
        });

        // Handle MethodNotAllowedHttpException
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('up')) {
                $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');
                $isAjax = $request->ajax();
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $ex) {
                    $message = 'JWTAuth - TokenExpiredException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenExpiredException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenExpiredException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $ex) {
                    $message = 'JWTAuth - TokenInvalidException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenInvalidException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenInvalidException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $ex) {
                    $message = 'JWTAuth - JWTException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - JWTException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - JWTException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                }

                $message = 'MethodNotAllowedHttpException for route: '. $e->getMessage();
                Log::error($message);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => '405 - HTTP Method Not Allowed',
                        'errStatus' => $message
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $ex->getMessage());
                }
                NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '405 - HTTP Method Not Allowed', $message);
                return response()->json([
                    'status' => 405,
                    'message' => '405 - HTTP Method Not Allowed',
                    'info' => $message,
                ], 405);
            }
        });

        // Handle RouteNotFoundException
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            if ($request->is('api/*') || $request->is('up')) {
                $uuid = AppHelper::Instance()->generateSingleUniqueUuid(appLogModel::class, 'processId');
                $isAjax = $request->ajax();
                try {
                    $user = JWTAuth::parseToken()->authenticate();
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $ex) {
                    $message = 'JWTAuth - TokenExpiredException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenExpiredException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenExpiredException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException $ex) {
                    $message = 'JWTAuth - TokenInvalidException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - TokenInvalidException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - TokenInvalidException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException $ex) {
                    $message = 'JWTAuth - JWTException: '. $ex->getMessage();
                    Log::error($message);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => 'JWTAuth - JWTException',
                            'errStatus' => $message
                        ]);
                    } catch (QueryException $ex) {
                        Log::error('Query Exception failed with: '. $ex->getMessage());
                    }
                    if ($isAjax) {
                        return response()->json([
                            'status' => 401,
                            'message' => '401 - Authentication Exception',
                            'info' => $message,
                            'errors' => 'JWTAuth - JWTException'
                        ], 401);
                    } else {
                        return redirect('/up');
                    }
                }

                $message = 'RouteNotFoundException for route: '. $e->getMessage();
                Log::error($message);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => '404 - Route Not Found',
                        'errStatus' => $message
                    ]);
                } catch (QueryException $ex) {
                    Log::error('Query Exception failed with: '. $ex->getMessage());
                }
                NotificationHelper::Instance()->sendRouteErrNotify($uuid, 'FAIL', '404 - Route Not Found', $message);
                return response()->json([
                    'status' => 404,
                    'message' => '404 - Route Not Found',
                    'info' => $message,
                ], 404);
            }
        });

    })->create();
