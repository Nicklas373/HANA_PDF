<?php

namespace App\Http\Controllers\proc;

use App\Helpers\AppHelper;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class mergeController extends Controller
{
    public function merge(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'file' => 'mimes:pdf|max:25600',
			'fileAlt' => '',
            'dropFile' => ''
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Merged failed !',$validator->messages());
                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->messages());
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
            $start = Carbon::parse($startProc);
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if ($request->hasfile('file')) {
                        foreach ($request->file('file') as $file) {
                            $str = rand(1000,10000000);
                            $randomizePdfFileName = 'pdf_merge_'.substr(md5(uniqid($str)), 0, 8);
                            $origFileName = $file->getClientOriginalName();
                            $file->storeAs('public/'.env('PDF_MERGE_TEMP'), $randomizePdfFileName.'.pdf');
                            $pdfResponse[] = Storage::disk('local')->url(env('PDF_MERGE_TEMP').'/'.$randomizePdfFileName.'.pdf');
                            $pdfOrigNameResponse[] = $origFileName;
                        }
                        return redirect()->back()->with([
                            'status' => true,
                            'pdfImplodeArray' => implode(',', $pdfResponse),
                            'pdfOrigName' => implode(',', $pdfOrigNameResponse),
                        ]);
                    } else {
                        $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfMerge')->insert([
                                'fileName' => null,
                                'fileSize' => null,
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
                                    'errReason' => 'PDF failed to upload !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
                    }
                } else if ($request->post('formAction') == "merge") {
					if(isset($_POST['fileAlt'])) {
                        if(isset($_POST['dropFile']))
						{
							$dropFile = array($request->post('dropFile'));
						} else {
							$dropFile = array();
						}
                        $str = rand();
                        $randomizePdfFileName = md5($str);
                        $pdfEncKey = bin2hex(random_bytes(16));
						$fileNameArray = $request->post('fileAlt');
                        $fileSizeArray = AppHelper::instance()->folderSize(Storage::disk('local')->path('public/'.env('PDF_MERGE_TEMP')));
                        $fileSizeInMB = AppHelper::instance()->convert($fileSizeArray, "MB");
                        $pdfArray = scandir(Storage::disk('local')->path('public/'.env('PDF_MERGE_TEMP')));
                        $pdfStartPages = 1;
                        $pdfPreProcessed_Location = env('PDF_MERGE_TEMP');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('merge');
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            foreach($pdfArray as $value) {
                                if (strlen($value) >= 4) {
                                    $arrayCount = 1;
                                    $arrayOrder = strval($arrayCount);
                                    $pdfName = $ilovepdfTask->addFile(Storage::disk('local')->path('public/'.$pdfPreProcessed_Location.'/'.$value));
                                    $arrayCount += 1;
                                }
                            }
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
                        }
                        $tempPDFfiles = glob(Storage::disk('local')->path('public/'.$pdfPreProcessed_Location.'/*'));
                        foreach($tempPDFfiles as $file){
                            if(is_file($file)) {
                                unlink($file);
                            }
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/merged.pdf'))) {
                            Storage::disk('local')->move('public/'.$pdfProcessed_Location.'/merged.pdf', 'public/'.$pdfProcessed_Location.'/'.$randomizePdfFileName.'.pdf');
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$randomizePdfFileName.'.pdf');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfMerge')->insert([
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
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
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($fileNameArray, $fileSizeInMB, $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
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
                                DB::table('pdfMerge')->insert([
                                    'fileName' => null,
                                    'fileSize' => null,
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
                                        'errReason' => 'Failed to download file from iLovePDF API !',
                                        'errApiReason' => null
                                ]);
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
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
                            DB::table('pdfMerge')->insert([
                                'fileName' => null,
                                'fileSize' => null,
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
                                    'errReason' => 'PDF failed to upload !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'PDF failed to upload !', 'null');
                            return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
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
                        DB::table('pdfMerge')->insert([
                            'fileName' => null,
                            'fileSize' => null,
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
                                'errReason' => 'INVALID_REQUEST_ERROR !',
                                'errApiReason' => null
                        ]);
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'INVALID_REQUEST_ERROR !', 'null');
                        return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                    }
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
                    DB::table('pdfMerge')->insert([
                        'fileName' => null,
                        'fileSize' => null,
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
                            'errReason' => 'REQUEST_ERROR_OUT_OF_BOUND !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'REQUEST_ERROR_OUT_OF_BOUND !', 'null');
                    return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Database connection error !', $ex->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>$uuid])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify('', '', $uuid, 'FAIL', 'Eloquent transaction error !', $e->getMessage());
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
			}
        }
    }
}
