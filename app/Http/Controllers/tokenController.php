<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class tokenController extends Controller
{
    public function getToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();
            return response()->json([
                'status' => 401,
                'message' => 'Validation failed',
                'data' => null,
                'errors' => $errors
            ], 401);
        }

        $origToken = env('TOKEN_GENERATE');
        $inputToken = $request->input('token');
        $hashedInputToken = hash('sha512', $inputToken);

        if ($hashedInputToken !== $origToken) {
            return response()->json([
                'status' => 200,
                'message' => 'Request generated',
                'token' => null,
                'errors' => 'Token verification failed'
            ], 200);
        } else {
            $token = $request->session()->token();
            $token = csrf_token();
            return response()->json([
                'status' => 200,
                'message' => 'Request generated',
                'token' => $token,
                'errors' => null
            ], 200);
        }
    }
}
