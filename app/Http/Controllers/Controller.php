<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected function returnCoreMessage($status, $message, $fileName, $fileSource, $proc, $procId, $curFileSize, $newFileSize, $compMethod, $errors) {
        if ($proc == 'compress') {
            return response()->json([
                'status' => $status,
                'message' => $message,
                'fileName' => $fileName,
                'fileSource' => $fileSource,
                'proc' => $proc,
                'groupId' => $procId,
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
                'groupId' => $procId,
                'errors' => $errors
            ], $status);
        }
    }

    protected function returnDataMesage($status, $message, $data, $groupId, $notification, $errors)
    {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'data' => $data,
            'groupId' => $groupId,
            'notification' => $notification,
            'errors' => $errors
        ], $status);
    }

    protected function returnFileMesage($status, $message, $files, $errors)
    {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'files' => $files,
            'errors' => $errors
        ], $status);
    }

    protected function returnLimitMessage($status, $message, $remaining, $total, $errors) {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'remaining' => $remaining,
            'total' => $total,
            'errors' => $errors
        ], $status);
    }

    protected function returnTokenMessage($status, $message, $info, $access_token, $token, $expire)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'info' => $info,
            'access_token' => $access_token,
            'token_type' => $token,
            'expires_in' => $expire,
        ], $status);
    }

    protected function returnVersioningMessage($status, $message, $beVersioning, $beGitVersioning, $feVersioning, $feGitVersioning, $errors)
    {
        return response()->json([
            'status' => $status,
            'message'=> $message,
            'beVersioning' => $beVersioning,
            'beGitVersioning' => $beGitVersioning,
            'feVersioning' => $feVersioning,
            'feGitVersioning' => $feGitVersioning,
            'errors' => $errors
        ], $status);
    }
}
