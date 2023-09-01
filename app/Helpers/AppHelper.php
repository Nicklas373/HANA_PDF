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
	} else if($unit == "MB") {
		return $fileSize = number_format(round($size / 1024 / 1024,4), 2) . ' MB';
	} else if($unit == "GB") {
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

    function getFtpResponse($download_file, $proc_file){
	$ftp_server = env('FTP_SERVER');
	$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
	$login = ftp_login($ftp_conn, env('FTP_USERNAME'), env('FTP_USERPASS'));

	if (ftp_get($ftp_conn, $download_file, $proc_file, FTP_BINARY)) {
		return true;
	} else {
		return false;
	}

	ftp_close($ftp_conn);
    }

    public static function instance()
    {
         return new AppHelper();
    }
}
