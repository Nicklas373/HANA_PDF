<?php
namespace App\Helpers;

use App\Models\appLogModel;
use App\Models\notifyLogModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

class AppHelper
{
    function checkWebAvailable($url){
        try {
            $response = Http::timeout(5)->get($url);
            if ($response->successful()) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    function getCurrentTimeZone() {
        date_default_timezone_set('Asia/Jakarta');
        $currentDateTime = date('Y-m-d H:i:s');
        return $currentDateTime;
    }

    function generateSingleUniqueUuid($customModel, $customColumn) {
        $startProc = Carbon::now()->format('Y-m-d H:i:s');
        $uniqueID = Uuid::uuid4();
        // do {
        //     $uniqueID = Uuid::uuid4();
        // } while (
        //     $customModel::where($customColumn, $uniqueID)->exists()
        // );
        $end = Carbon::now();
        $duration = $end->diffInSeconds(Carbon::parse($startProc));
        Log::Info('New single unique UUID has been generated with response time: '.$duration.' seconds');
        return $uniqueID->toString();
    }

    function generateUniqueUuid($customModel, $customColumn) {
        $startProc = Carbon::now()->format('Y-m-d H:i:s');
        $uniqueID = Uuid::uuid4();
        // do {
        //     $uniqueID = Uuid::uuid4();
        // } while (
        //     appLogModel::where($customColumn, $uniqueID)->exists() ||
        //     $customModel::where($customColumn, $uniqueID)->exists()
        // );
        $end = Carbon::now();
        $duration = $end->diffInSeconds(Carbon::parse($startProc));
        Log::Info('New unique UUID has been generated with response time: '.$duration.' seconds');
        return $uniqueID->toString();
    }

    public static function instance()
    {
         return new AppHelper();
    }
}
