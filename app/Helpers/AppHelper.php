<?php
namespace App\Helpers;

class AppHelper
{
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
        $ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
        $login = ftp_login($ftp_conn, env('FTP_USERNAME'), env('FTP_USERPASS'));
        $login_pasv = ftp_pasv($ftp_conn, true) or die("Cannot switch to passive mode");

        if (ftp_get($ftp_conn, $download_file, $proc_file, FTP_BINARY) ) {
            return true;
        } else {
            die("Cannot find specified file");
            ftp_close($ftp_conn);
            return false;
        }

        ftp_close($ftp_conn);
    }

    function get_guid() {
        if (function_exists('com_create_guid') === true)
            return trim(com_create_guid(), '{}');
        $data = PHP_MAJOR_VERSION < 7 ? openssl_random_pseudo_bytes(16) : random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // Set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // Set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    function getUserIpAddr(){
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public static function instance()
    {
         return new AppHelper();
    }
}
