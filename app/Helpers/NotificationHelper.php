<?php
namespace App\Helpers;

use Telegram\Bot\Laravel\Facades\Telegram;

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
        } else {
            $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
        }

        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA PDF Error Notification</b>\n\nFilename: <b>".$newProcFile."</b>\nFileSize: <b>".$newFileSize."</b>\nEnvironment: <b>SIT</b>\nStatus: <b>".$status."</b>\nProcess Id: <b>".$processId."</b>\nStart At: <b>".$CurrentTime."</b>\nError Reason: <b>".$errReason."</b>\nError Log: <pre><code>".$errCode."</code></pre>";

        return Telegram::sendMessage([
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }

    function sendSchedErrNotify($schedName, $schedRuntime, $processId , $status, $errReason, $errCode) {
        $CurrentTime = AppHelper::instance()->getCurrentTimeZone();
        $message = "<b>HANA PDF Job Error Notification</b>\n\Job Name: <b>".$schedName."</b>\nJob Runtime: <b>".$schedRuntime."</b>\nEnvironment: <b>SIT</b>\nStatus: <b>".$status."</b>\nProcess Id: <b>".$processId."</b>\nStart At: <b>".$CurrentTime."</b>\nError Reason: <b>".$errReason."</b>\nError Log: <pre><code>".$errCode."</code></pre>";

        return Telegram::sendMessage([
            'chat_id' => env('TELEGRAM_CHAT_ID'),
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
}
