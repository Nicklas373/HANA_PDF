<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\HtmlpdfTask;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class htmltopdfController extends Controller
{
    public function html(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
		    'urlToPDF' => 'required',
	    ]);

        $uuid = AppHelper::Instance()->get_guid();

        // Carbon timezone
        date_default_timezone_set('Asia/Jakarta');
        $now = Carbon::now('Asia/Jakarta');
        $startProc = $now->format('Y-m-d H:i:s');

        if($validator->fails()) {
            try {
                DB::table('appLogs')->insert([
                    'processId' => $uuid,
                    'errReason' => $validator->messages(),
                    'errApiReason' => null
                ]);
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','HTML to PDF process failed !',$validator->messages());
                return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->getMessage());
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
            $start = Carbon::parse($startProc);
            $str = rand(1000,10000000);
            $pdfEncKey = bin2hex(random_bytes(16));
            $pdfDefaultFileName ='pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
            $pdfProcessed_Location = env('PDF_DOWNLOAD');
            $pdfUpload_Location = env('PDF_UPLOAD');
            $pdfUrl = $request->post('urlToPDF');
            $newUrl = '';
            if (AppHelper::Instance()->checkWebAvailable($pdfUrl)) {
                $newUrl = $pdfUrl;
            } else {
                if (AppHelper::Instance()->checkWebAvailable('https://'.$pdfUrl)) {
                    $newUrl = 'https://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('http://'.$pdfUrl)) {
                    $newUrl = 'http://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('www.'.$pdfUrl)) {
                    $newUrl = 'www.'.$pdfUrl;
                } else {
                    $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfHtml')->insert([
                            'urlName' => $request->post('urlToPDF'),
                            'result' => false,
                            'processId' => $uuid,
                            'procStartAt' => $startProc,
                            'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                            'procDuration' =>  $duration->s.' seconds'
                        ]);
                        DB::table('appLogs')
                            ->where('processId', '=', $uuid)
                            ->update([
                                'processId' => $uuid,
                                'errReason' => '404',
                                'errApiReason' => null
                        ]);
                        NotificationHelper::Instance()->sendErrNotify($newUrl, '', $uuid, 'FAIL', 'HTML to PDF process failed !', '404');
                        return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($newUrl, '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($newUrl, '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                    }
                }
            }
            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setEncryptKey($pdfEncKey);
                $ilovepdfTask->setEncryption(true);
                $pdfFile = $ilovepdfTask->addUrl($newUrl);
                $ilovepdfTask->setOutputFileName($pdfDefaultFileName);
                $ilovepdfTask->execute();
                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
            } catch (StartException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on StartException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (AuthException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on AuthException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (UploadException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on UploadException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (ProcessException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on ProcessException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (DownloadException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on DownloadException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (TaskException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on TaskException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (PathException $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on PathException',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Exception $e) {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'iLovePDF API Error !, Catch on Exception',
                            'errApiReason' => $e->getMessage()
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', 'null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            }
            if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'))) {
                $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf');
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => true,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                    ]);
                    return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), $fileSize, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            } else {
                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfHtml')->insert([
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'processId' => $uuid,
                        'procStartAt' => $startProc,
                        'procEndAt' => AppHelper::instance()->getCurrentTimeZone(),
                        'procDuration' =>  $duration->s.' seconds'
                    ]);
                    DB::table('appLogs')
                        ->where('processId', '=', $uuid)
                        ->update([
                            'processId' => $uuid,
                            'errReason' => 'Failed to download converted file from iLovePDF API !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'),'', $uuid, 'FAIL','HTML to PDF process failed !','null');
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($request->post('urlToPDF'), '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
            }
        }
    }
}
