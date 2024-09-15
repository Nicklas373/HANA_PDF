<?php

namespace App\Http\Controllers\Api\Auth;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function getToken()
    {
        $uuid = AppHelper::Instance()->get_guid();
        $credentials = request(['email', 'password']);

        if (!$token = $this->guard()->attempt($credentials)) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => 'Auth breach detected, requested with '.json_encode($credentials),
                    'errStatus' => 'Access unauthorized'
                ]);
                NotificationHelper::Instance()->sendErrGlobalNotify('api/v1/auth/getToken', 'Auth', 'FAIL', $uuid,'Access unauthorized', 'Auth breach detected, requested with '.json_encode($credentials), false);
                return $this->returnDataMesage(
                    401,
                    'Access unauthorized',
                    null,
                    null,
                    'Auth breach detected'
                );
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrGlobalNotify('api/v1/auth/getToken', 'Auth', 'FAIL', $uuid,'Database connection error', $ex->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Database connection error',
                    null,
                    null,
                    $ex->getMessage()
                );
            } catch (\Exception $e) {
                NotificationHelper::Instance()->sendErrGlobalNotify('api/v1/auth/getToken', 'Auth', 'FAIL', $uuid,'Unknown Exception', $e->getMessage(), false);
                return $this->returnDataMesage(
                    500,
                    'Unknown Exception',
                    null,
                    null,
                    $e->getMessage()
                );
            }
        }

        return $this->respondWithToken($token);
    }

    public function initToken() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
        ]);

        if($validator->fails()){
            return $this->returnTokenMessage(
                400,
                'Validation failed',
                $validator->messages()->first(),
                null,
                null,
                null
            );
        }

        if (User::exists()) {
            return $this->returnTokenMessage(
                401,
                'Auth registration failed',
                'There is another token has registered before',
                null,
                null,
                null
            );
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->save();

        return $this->returnTokenMessage(
            201,
            'User has been registered',
            $user,
            null,
            null,
            null
        );
    }

    public function refreshToken()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    public function revokeToken()
    {
        $token = JWTAuth::getToken();
        Log::INFO($token);
        $invalidate = JWTAuth::invalidate($token);

        $this->guard()->logout();

        if ($invalidate) {
            return $this->returnTokenMessage(
                200,
                'Token revoked',
                $token,
                null,
                null,
                $invalidate
            );
        }  else {
            return $this->returnTokenMessage(
                400,
                'Failed to revoked token',
                null,
                null,
                null,
                null
            );
       }
    }

    protected function respondWithToken($token)
    {
        return $this->returnTokenMessage(
            200,
            'Token generated',
            Auth::user(),
            'bearer',
            $token,
            $this->guard()->factory()->getTTL() * 60
        );
    }
    
    public function guard()
    {
        return Auth::guard();
    }
}
