<?php
namespace App\Helpers;

use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    public static function instance()
    {
         return new NotificationHelper();
    }

    function sendErrNotify($procFile, $fileSize, $processId, $status, $proc, $errReason, $errCode) {
        if ($procFile == null || $procFile == "") {
            $newProcFile = 'null';
        } else {
            $newProcFile = $procFile;
        }

        if ($fileSize == null || $fileSize == "") {
            $newFileSize = '0.0 MB';
        } else if (strstr($fileSize, "MB")) {
            $newFileSize = $fileSize;
        } else {
            $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
        }

        if ($proc == "compress") {
            $newRoute = 'api/v1/core/compress';
        } else if ($proc == "convert" || $proc == "cnvToXls" || $proc == "cnvToPptx" || $proc == "cnvToDocx" || $proc == "cnvToImg" || $proc == "pdfToImg") {
            $newRoute = 'api/v1/core/convert';
        } else if ($proc == "htmltopdf") {
            $newRoute = 'api/v1/core/html';
        } else if ($proc == "merge") {
            $newRoute = 'api/v1/core/merge';
        } else if ($proc == "split" || $proc == "split_delete") {
            $newRoute = 'api/v1/core/split';
        } else if ($proc == "watermark") {
            $newRoute = 'api/v1/core/watermark';
        } else {
            $newRoute = 'undefined';
        }

        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nServices: <b>Backend Services</b>
                    Source: <b>https://gw.hana-ci.com</b>
                    Endpoint: <b>".$newRoute.
                    "</b>\nProcess: <b>".$proc.
                    "</b>\nType: <b>Process Error</b>
                    \nFilename: <b>".$newProcFile.
                    "</b>\nFileSize: <b>".$newFileSize.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "</b>\nStatus: <b>".$status.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nStart At: <b>".$CurrentTime.
                    "</b>\nError Reason: <b>".$errReason.
                    "</b>\nError Log: <pre><code>".$errCode.
                    "</code></pre>";

        try {
            $response = Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            $messageId = $response->getMessageId();
            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response,
                    'notifyErrStatus' => $errReason,
                    'notifyErrMessage' => $errCode
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            try {
                if ($e->getHttpStatusCode() == null) {
                  $httpStatus = null;
                } else {
                  $httpStatus = $e->getHttpStatusCode();
                }
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => $e->getMessage(),
                    'notifyResponse' => null,
                    'notifyErrStatus' => $httpStatus,
                    'notifyErrMessage' => $e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => null,
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }

    function sendRouteErrNotify($processId, $status, $errReason, $errRoute, $errCode, $ip) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nServices: <b>Backend Services</b>
                    Source: <b>https://gw.hana-ci.com</b>
                    Endpoint: <b>".$errRoute.
                    "</b>\nType: <b>Route Error</b>
                    </b>\nEnvironment: <b>".env('APP_ENV').
                    "</b>\nStatus: <b>".$status.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nIP Address: <b>".$ip.
                    "</b>\nStart At: <b>".$CurrentTime.
                    "</b>\nError Reason: <b>".$errReason.
                    "</b>\nError Log: <pre><code>".$errCode.
                    "</code></pre>";
        try {
            $response = Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            $messageId = $response->getMessageId();

            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response,
                    'notifyErrStatus' => $errReason,
                    'notifyErrMessage' => $errCode
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            try {
                if ($e->getHttpStatusCode() == null) {
                  $httpStatus = null;
                } else {
                  $httpStatus = $e->getHttpStatusCode();
                }
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => $e->getMessage(),
                    'notifyResponse' => null,
                    'notifyErrStatus' => $httpStatus,
                    'notifyErrMessage' => $e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => null,
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }

    function sendSchedErrNotify($schedName, $schedRuntime, $processId , $status, $errReason, $errCode) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nServices: <b>Backend Services</b>
                    Source: <b>https://gw.hana-ci.com</b>
                    Route: <b>".$errRoute.
                    "</b>\nType: <b>Jobs Error</b>
                    </b>\nJob Runtime: <b>".$schedRuntime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "</b>\nStatus: <b>".$status.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nStart At: <b>".$CurrentTime.
                    "</b>\nError Reason: <b>".$errReason.
                    "</b>\nError Log: <pre><code>".$errCode.
                    "</code></pre>";
        try {
            $response = Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            $messageId = $response->getMessageId();

            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response,
                    'notifyErrStatus' => $errReason,
                    'notifyErrMessage' => $errCode
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            try {
                if ($e->getHttpStatusCode() == null) {
                  $httpStatus = null;
                } else {
                  $httpStatus = $e->getHttpStatusCode();
                }
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => $e->getMessage(),
                    'notifyResponse' => null,
                    'notifyErrStatus' => $httpStatus,
                    'notifyErrMessage' => $e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => null,
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }
}
