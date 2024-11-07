<?php
namespace App\Helpers;

use App\Models\appLogModel;
use App\Models\notifyLogModel;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class NotificationHelper
{
    public static function instance()
    {
         return new NotificationHelper();
    }

    function sendRouteErrNotify($processId, $status, $errReason, $errCode) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $uuid = AppHelper::Instance()->generateUniqueUuid(notifyLogModel::class, 'processId');
        $message = "<b>HANA API Alert</b>
                    \nStatus: <b>".$status."</b>".
                    "\nStart At: <b>".$CurrentTime.
                    "</b>\nEnvironment: <b>".env('APP_ENV').
                    "\n\n</b>Services: <b>Frontend Services</b>".
                    "\nSource: <b>https://gw.hana-ci.com</b>".
                    "\nGroup Id: <b>".$processId.
                    "</b>\n\nError Type: <b>Route Error</b>".
                    "\nError Reason: <b>".$errReason.
                    "</b>\nError Log: <pre><code>".$errCode.
                    "</code></pre>";
        try {
            $response = Telegram::sendMessage([
                'chat_id' => env('TELEGRAM_CHAT_ID'),
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            $messageId = $response->getMessageId();
            appLogModel::create([
                'processId' => $uuid,
                'groupId' => $processId,
                'errReason' => null,
                'errStatus' => null
            ]);
            notifyLogModel::create([
                'processId' => $uuid,
                'notifyName' => 'Telegram SDK',
                'notifyResult' => true,
                'notifyMessage' => 'Message has been sent !',
                'notifyResponse' => $response
            ]);
        } catch (\Telegram\Bot\Exceptions\TelegramResponseException $e) {
            if ($e->getHttpStatusCode() == null) {
                $httpStatus = null;
            } else {
                $httpStatus = $e->getHttpStatusCode();
            }
            appLogModel::where('processId', '=', $uuid)
                ->update([
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $errReason
                ]);
            notifyLogModel::where('processId', '=', $uuid)
                ->update([
                    'notifyResult' => false,
                    'notifyMessage' => 'TelegramResponseException',
                    'notifyResponse' => $e->getMessage().' | '.$httpStatus.' | '.$e->getErrorType()
                ]);
        } catch (\Exception $e) {
            appLogModel::where('processId', '=', $uuid)
                ->update([
                    'errReason' => 'TelegramResponseException',
                    'errStatus' => $errReason
                ]);
            notifyLogModel::where('processId', '=', $uuid)
                ->update([
                    'notifyResult' => false,
                    'notifyMessage' => 'Unexpected handling exception !',
                    'notifyResponse' => $e->getMessage()
                ]);
        }
    }
}
