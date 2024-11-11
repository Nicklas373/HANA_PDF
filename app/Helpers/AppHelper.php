<?php
namespace App\Helpers;

use App\Models\appLogModel;
use App\Models\jobLogModel;
use App\Models\notifyLogModel;
use App\Models\compressModel;
use App\Models\cnvModel;
use App\Models\htmlModel;
use App\Models\mergeModel;
use App\Models\splitModel;
use App\Models\watermarkModel;
use Illuminate\Support\Facades\Http;
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

    function generateUniqueUuid($customModel, $customColumn) {
        $startProc = Carbon::now()->format('Y-m-d H:i:s');
        //$uniqueID = Uuid::uuid4();
        if ($customColumn !== 'processId') {
            do {
                $uniqueID = Uuid::uuid4();
            } while (
                $customModel::where($customColumn, $uniqueID)->exists()
            );
        } else {
            do {
                $uniqueID = Uuid::uuid4();
            } while (
                appLogModel::where($customColumn, $uniqueID)->exists() ||
                jobLogModel::where($customColumn, $uniqueID)->exists() ||
                notifyLogModel::where($customColumn, $uniqueID)->exists() ||
                compressModel::where($customColumn, $uniqueID)->exists() ||
                cnvModel::where($customColumn, $uniqueID)->exists() ||
                htmlModel::where($customColumn, $uniqueID)->exists() ||
                mergeModel::where($customColumn, $uniqueID)->exists() ||
                splitModel::where($customColumn, $uniqueID)->exists() ||
                watermarkModel::where($customColumn, $uniqueID)->exists()
            );
        }
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
