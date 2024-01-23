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

    function sendErrNotify($procFile, $fileSize, $processId, $status, $errReason, $errCode) {
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

        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA PDF Error Notification</b>\n\nFilename: <b>".$newProcFile.
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
                    'notifyMessage' => $response->getMessage(),
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => null
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
        $message = "<b>HANA PDF Error Notification</b>\n\nRoute: <b>".$errRoute.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
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
                    'notifyMessage' => $response->getMessage(),
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => null
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
                    'notifyErrStatus' => $httpStatus,
                    'notifyErrMessage' => $e->getRawResponse()
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
        $message = "<b>HANA PDF Job Error Notification</b>\n\nJob Name: <b>".$schedName.
                    "</b>\nJob Runtime: <b>".$schedRuntime.
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
                    'notifyMessage' => $response->getMessage(),
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => null
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
                    'notifyErrStatus' => null,
                    'notifyErrMessage' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }
}
