<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function returnTokenMessage($status, $message, $token, $expire)
    {
        if ($status == 200) {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $expire,
            ], $status);
        } else {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'access_token' => $token,
                'token_type' => null,
                'expires_in' => $expire,
            ], $status);
        }
    }

    protected function returnCoreMessage($status, $message, $fileName, $fileSource, $proc, $procId, $curFileSize, $newFileSize, $compMethod, $errors) {
        if ($proc == 'compress') {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'fileName' => $fileName,
                'fileSource' => $fileSource,
                'proc' => $proc,
                'processId' => $procId,
                'curFileSize' => $curFileSize,
                'newFileSize' => $newFileSize,
                'compMethod' => $compMethod,
                'errors' => $errors
            ], $status);
        } else {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'fileName' => $fileName,
                'fileSource' => $fileSource,
                'proc' => $proc,
                'processId' => $procId,
                'errors' => $errors
            ], $status);
        }
    }

    protected function returnDataMesage($status, $message, $remaining, $total, $data_1, $data_2, $notification, $errors)
    {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'remaining' => $remaining,
            'total' => $total,
            'data_1' => $data_1,
            'data_2' => $data_2,
            'notification' => $notification,
            'errors' => $errors
        ], $status);
    }

    protected function returnFileMesage($status, $message, $fileSource, $fileName, $errors)
    {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'fileName' => $fileName,
            'fileSource' => $fileSource,
            'errors' => $errors
        ], $status);
    }
}
