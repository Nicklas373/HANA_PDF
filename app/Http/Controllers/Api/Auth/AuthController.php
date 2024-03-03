<?php

namespace App\Http\Controllers\Api\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function token()
    {
        $credentials = request(['email', 'password']);

        if ($token = auth('api')->validate($credentials)) {
            $user = User::where('email', $credentials['email'])->first();
            if ($user && Hash::check($credentials['password'], $user->password)) {
                $token = auth('api')->attempt($credentials);
                if ($token) {
                    return $this->returnTokenMessage(
                        200,
                        'Token generated',
                        $token,
                        env('JWT_TTL')
                    );
                }
            } else {
                return $this->returnTokenMessage(401, 'Access unauthorized', null, null);
            }
        } else {
            return $this->returnTokenMessage(403, 'Access forbidden', null, null);
        }
    }

    public function revoke()
    {
         // get token
         $token = JWTAuth::getToken();

         // invalidate token
         $invalidate = JWTAuth::invalidate($token);

         if ($invalidate) {
            return $this->returnTokenMessage(200, 'Token revoked', null, null);
         }  else {
            return $this->returnTokenMessage(200, 'Failed to revoked token !', null, null);
        }
    }
}
