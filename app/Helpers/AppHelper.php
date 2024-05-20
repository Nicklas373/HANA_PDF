<?php
namespace App\Helpers;

class AppHelper
{
    function checkWebAvailable($url){
        if(!filter_var($url, FILTER_VALIDATE_URL)){
            return false;
        }

        $curlInit = curl_init($url);

        curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
        curl_setopt($curlInit,CURLOPT_HEADER,true);
        curl_setopt($curlInit,CURLOPT_NOBODY,true);
        curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

        $response = curl_exec($curlInit);

        curl_close($curlInit);

        return $response?true:false;
    }

    function count($path)
    {
        $pdf = file_get_contents($path);
        $number = preg_match_all("/\/Page\W/", $pdf, $dummy);
        return $number;
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

    function get_guid() {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    function getUserIpAddr(){
        if( !empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function instance()
    {
         return new AppHelper();
    }
}
