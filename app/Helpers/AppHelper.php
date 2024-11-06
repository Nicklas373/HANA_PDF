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

    function convert($size,$unit)
    {
        if ($unit == "KB") {
            return $fileSize = number_format(round($size / 1024,4), 2) . ' KB';
        } else if ($unit == "MB") {
            return $fileSize = number_format(round($size / 1024 / 1024,4), 2) . ' MB';
        } else if ($unit == "GB") {
            return $fileSize = number_format(round($size / 1024 / 1024 / 1024,4), 2) . ' GB';
        }
    }

    function folderSize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : folderSize($each);
        }

        return $size;
    }

    function getCurrentTimeZone() {
        date_default_timezone_set('Asia/Jakarta');
        $currentDateTime = date('Y-m-d H:i:s');
        return $currentDateTime;
    }

    function getFtpResponse($download_file, $proc_file){
        $ftp_server = env('FTP_SERVER');
        $ftp_conn = ftp_connect($ftp_server);
        $login = ftp_login($ftp_conn, env('FTP_USERNAME'), env('FTP_USERPASS'));
        $login_pasv = ftp_pasv($ftp_conn, true);

        if (ftp_size($ftp_conn, $proc_file) != -1) {
            if (ftp_get($ftp_conn, $download_file, $proc_file, FTP_BINARY)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }

        ftp_close($ftp_conn);
    }

    function getGitCommitHash()
    {
        $gitSourcePath = base_path('.git/');

        if (!file_exists($gitSourcePath)) {
            return null;
        }

        $headFile = $gitSourcePath.'HEAD';
        $head = trim(file_get_contents($headFile));

        if (strpos($head, 'ref: ') === 0) {
            $ref = substr($head, 5);
            $commitHashFile = $gitSourcePath . $ref;

            if (!file_exists($commitHashFile)) {
                return null;
            }

            $hash = trim(file_get_contents($commitHashFile));
        } else {
            $hash = $head;
        }

        return substr($hash, 0, 7);
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
