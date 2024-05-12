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
                    \nStatus: <b>".$status."</b>".
                    "\nStart At: <b>".$CurrentTime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "\n\n</b>Services: <b>Backend Services</b>".
                    "\nSource: <b>https://gw.hana-ci.com</b>".
                    "\nEndpoint: <b>".$newRoute.
                    "</b>\n\nProcess: <b>".$proc.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nType: <b>Process Error</b>".
                    "\n\nFilename: <b>".$newProcFile.
                    "</b>\nFileSize: <b>".$newFileSize.
                    "</b>\n\nError Reason: <b>".$errReason.
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => null,
                    'errStatus' => null,
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage()+' | '+$httpStatus+' | '+$e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'Unexpected handling exception !',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }

    function sendRouteErrNotify($processId, $status, $errReason, $errRoute, $errCode, $ip) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nStatus: <b>".$status."</b>".
                    "\nStart At: <b>".$CurrentTime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "\n\n</b>Services: <b>Backend Services</b>".
                    "\nSource: <b>https://gw.hana-ci.com</b>".
                    "\nEndpoint: <b>".$errRoute.
                    "</b>\n\nIP Address: <b>".$ip.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nType: <b>Route Error</b>".
                    "\n\nError Reason: <b>".$errReason.
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => null,
                    'errStatus' => null,
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage()+' | '+$httpStatus+' | '+$e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'Unexpected handling exception !',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }

    function sendSchedErrNotify($schedName, $schedRuntime, $processId , $status, $errReason, $errCode) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nStatus: <b>".$status."</b>".
                    "\nStart At: <b>".$CurrentTime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "\n\n</b>Services: <b>Backend Services</b>".
                    "\nSource: <b>https://gw.hana-ci.com</b>".
                    "\nEndpoint: <b>".$schedName.
                    "</b>\n\nProcess: <b>".$schedRuntime.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nType: <b>Jobs Error</b>".
                    "\n\nError Reason: <b>".$errReason.
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => null,
                    'errStatus' => null,
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage()+' | '+$httpStatus+' | '+$e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'Unexpected handling exception !',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }

    function sendDailyTaskNotify($compTotalScs, $compTotalErr, $cnvTotalScs, $cnvTotalErr, $htmlTotalScs, $htmlTotalErr,
                                $mergeTotalScs, $mergeTotalErr, $splitTotalScs, $splitTotalErr, $watermarkTotalScs,
                                $watermarkTotalErr, $taskStatus, $errReason, $errCode) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $processId = AppHelper::instance()->get_guid();
        if ($taskStatus) {
            $CountTotalScsProc = $compTotalScs + $cnvTotalScs + $htmlTotalScs + $mergeTotalScs + $splitTotalScs + $watermarkTotalScs;
            $CountTotalErrProc = $compTotalErr + $cnvTotalErr + $htmlTotalErr + $mergeTotalErr + $splitTotalErr + $watermarkTotalErr;
            $message = "<b>HANA API Daily Report Alert</b>".
                        "\n\nReported At: <b>".$CurrentTime.
                        "</b>\nReport Status: <b>Success".
                        "</b>\nEnvironment: <b>".env('APP_ENV').
                        "</b>\nServices: <b>Backend Services</b>".
                        "\nSource: <b>https://gw.hana-ci.com</b>".
                        "\n\n<b>Compress Task\n</b>Success: <b>".$compTotalScs."</b>\nError: <b>".$compTotalErr."</b>".
                        "\n\n<b>Convert Task\n</b>Success: <b>".$cnvTotalScs."</b>\nError: <b>".$cnvTotalErr."</b>".
                        "\n\n<b>HTMLtoPDF Task\n</b>Success: <b>".$htmlTotalScs."</b>\nError: <b>".$htmlTotalErr."</b>".
                        "\n\n<b>Merge Task\n</b>Success: <b>".$mergeTotalScs."</b>\nError: <b>".$mergeTotalErr."</b>".
                        "\n\n<b>Split Task\n</b>Success: <b>".$splitTotalScs."</b>\nError: <b>".$splitTotalErr."</b>".
                        "\n\n<b>Watermark Task\n</b>Success: <b>".$watermarkTotalScs."</b>\nError: <b>".$watermarkTotalErr."</b>".
                        "\n\n<b>Total Task\n</b>Success: <b>".$CountTotalScsProc."</b>\nError: <b>".$CountTotalErrProc."</b>";
        } else {
            $message = "<b>HANA API Daily Report Alert</b>".
                        "\n\nReported At: <b>".$CurrentTime.
                        "</b>\nReport Status: <b>FAIL".
                        "</b>\nEnvironment: <b>".env('APP_ENV').
                        "</b>\nServices: <b>Backend Services</b>".
                        "\nSource: <b>https://gw.hana-ci.com</b>".
                        "\n\nError Reason: <b>".$errReason.
                        "</b>\nError Log: <pre><code>".$errCode.
                        "</code></pre>";
        }
        try {
            $response = Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_REPORT_ID'),
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            $messageId = $response->getMessageId();
            try {
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => null,
                    'errStatus' => null,
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => true,
                    'notifyMessage' => 'Message has been sent !',
                    'notifyResponse' => $response
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
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage()+' | '+$httpStatus+' | '+$e->getErrorType()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        } catch (\Exception $e) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $processId,
                    'errReason' => 'Unexpected handling exception !',
                    'errStatus' => $e->getMessage(),
				]);
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }
}
