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

    function sendRouteErrNotify($processId, $status, $errReason, $errRoute, $errCode, $ip) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA API Alert</b>
                    \nStatus: <b>".$status."</b>".
                    "\nStart At: <b>".$CurrentTime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "\n\n</b>Services: <b>Frontend Services</b>".
                    "\nSource: <b>https://pdf.hana-ci.com</b>".
                    "\nEndpoint: <b>".$errRoute.
                    "</b>\n\nIP Address: <b>".$ip.
                    "</b>\nProcess Id: <b>".$processId.
                    "</b>\nType: <b>Route Error</b>".
                    "\n\nError Reason: <b>".$errReason.
                    "</b>\nError Log: <pre><code>".$errCode.
                    "</code></pre>";
        $checkValidate = DB::table('appLogs')::where('processId', '=', $processId)->count();
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
                DB::table('notifyLogs')->insert([
                    'processId' => $processId,
                    'notifyName' => 'Telegram SDK',
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage().' | '.$httpStatus.' | '.$e->getErrorType()
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
                    'notifyResponse' => $e->getMessage()
                ]);
            } catch (QueryException $ex) {
                Log::error('Query Exception failed with: '. $ex->getMessage());
            }
        }
    }
}
