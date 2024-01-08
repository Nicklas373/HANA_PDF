<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\watermarkModel;
use App\Models\appLogsModel;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\WatermarkTask;
use Ilovepdf\Exceptions\StartException;
use Ilovepdf\Exceptions\AuthException;
use Ilovepdf\Exceptions\UploadException;
use Ilovepdf\Exceptions\ProcessException;
use Ilovepdf\Exceptions\DownloadException;
use Ilovepdf\Exceptions\TaskException;
use Ilovepdf\Exceptions\PathException;

class watermarkController extends Controller
{
	public function pdf_watermark(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25600',
			'fileAlt' => '',
			'wmfile' => 'mimes:jpg,png,jpeg|max:5120',
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
                return redirect()->back()->withErrors(['error'=>'File validation failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
            $start = Carbon::parse($startProc);
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$str = rand(1000,10000000);
						$pdfUpload_Location = env('PDF_UPLOAD');
                        $file = $request->file('file');
                        $randomizePdfFileName = 'pdf_watermark_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
                        $fileSize = filesize($file);
						$pdfFileName = $file->getClientOriginalName();
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
                                'pdfOriName' => $pdfFileName,
                            ]);
						} else {
                            $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfWatermark')->insert([
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $fileSize,
                                    'watermarkFontFamily' => null,
                                    'watermarkFontStyle' => null,
                                    'watermarkFontSize' => null,
                                    'watermarkFontTransparency' => null,
                                    'watermarkImage' => null,
                                    'watermarkLayout' => null,
                                    'watermarkMosaic' => null,
                                    'watermarkRotation' => null,
                                    'watermarkStyle' => null,
                                    'watermarkText' => null,
                                    'watermarkPage' => null,
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
                                        'errReason' => 'PDF file not found on the server !',
                                        'errApiReason' => null
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                            }
						}
					} else {
                        $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => $randomizePdfFileName.'.pdf',
                                'fileSize' => $fileSize,
                                'watermarkFontFamily' => null,
                                'watermarkFontStyle' => null,
                                'watermarkFontSize' => null,
                                'watermarkFontTransparency' => null,
                                'watermarkImage' => null,
                                'watermarkLayout' => null,
                                'watermarkMosaic' => null,
                                'watermarkRotation' => null,
                                'watermarkStyle' => null,
                                'watermarkText' => null,
                                'watermarkPage' => null,
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
                            return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        } catch (\Exception $e) {
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
					}
				} else if ($request->post('formAction') == "watermark") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['isMosaic']))
						{
							$tempPDF = $request->post('isMosaic');
							$tempCompare = $tempPDF ? 'true' : 'false';
							$isMosaicDB = "true";
							$isMosaic = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						} else {
							$tempCompare = false ? 'true' : 'false';
							$isMosaicDB = "false";
							$isMosaic = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						}
						if(isset($_POST['watermarkFontFamily']))
						{
							$watermarkFontFamilyFetch = $request->post('watermarkFontFamily');
                            if (strcmp($watermarkFontFamilyFetch, 'Choose font family') == 0) {
                                $watermarkFontFamily = 'Arial';
                            } else {
                                $watermarkFontFamily = $watermarkFontFamilyFetch;
                            }
						} else {
							$watermarkFontFamily = 'Arial';
						}
						if(isset($_POST['watermarkFontSize']))
						{
							$watermarkFontSize = $request->post('watermarkFontSize');
						} else {
							$watermarkFontSize = '12';
						}
						if(isset($_POST['watermarkFontStyle']))
						{
                            $watermarkFontStyleFetch = $request->post('watermarkFontStyle');
                            if (strcmp($watermarkFontStyleFetch, 'Choose font style') == 0) {
                                $watermarkFontStyle = 'Regular';
                            } else {
                                $watermarkFontStyle = $watermarkFontStyleFetch;
                            }
						} else {
							$watermarkFontStyle = 'Regular';
						}
						if(isset($_POST['watermarkLayoutStyle']))
						{
							$watermarkLayoutFetch = $request->post('watermarkLayoutStyle');
                            if (strcmp($watermarkLayoutFetch, 'Choose layer style') == 0) {
                                $watermarkLayoutStyle = 'above';
                            } else {
                                $watermarkLayoutStyle = $watermarkLayoutFetch;
                            }
						} else {
							$watermarkLayoutStyle = 'above';
						}
						if(isset($_POST['watermarkRotation']))
						{
							$watermarkRotationFetch = $request->post('watermarkRotation');
                            if (strcmp($watermarkRotationFetch, 'Choose orientation degrees') == 0) {
                                $watermarkRotation = 0;
                            } else {
                                $watermarkRotation = $watermarkRotationFetch;
                            }
						} else {
							$watermarkRotation = 0;
						}
						if(isset($_POST['watermarkText']))
						{
							$watermarkText = $request->post('watermarkText');
						} else {
							$watermarkText = '';
						}
						if($request->hasfile('wmfile')) {
							$watermarkImage = $request->file('wmfile');
						} else {
							$watermarkImage = '';
						}
						if(isset($_POST['wmType']))
						{
							$watermarkStyle = $request->post('wmType');
						} else {
							$watermarkStyle = '';
						}
                        $str = rand();
                        $file = $request->post('fileAlt');
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfEncKey = bin2hex(random_bytes(16));
						$pdfName = basename($file);
                        $pdfNameWithoutExtention = basename($pdfName, '.pdf');
                        $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						$fileSize = filesize($pdfNewPath);
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        if($request->hasfile('wmfile')) {
                            $randomizeImageFileName = md5($str);
                        } else {
                            $randomizeImageFileName = 'null';
                        }
						if($watermarkStyle == "image") {
                            if(isset($_POST['watermarkPageImage']))
                            {
                                $watermarkInputPage = $request->post('watermarkPageImage');
                                if (is_string($watermarkInputPage)) {
                                    $watermarkPage = strtolower($watermarkInputPage);
                                } else {
                                    $watermarkPage = $watermarkInputPage;
                                }
                            } else {
                                $watermarkPage = 'all';
                            }
                            if(isset($_POST['watermarkFontImageTransparency']))
                            {
                                $watermarkFontTransparencyTemp = $request->post('watermarkFontImageTransparency');
                                $watermarkFontTransparency = intval($watermarkFontTransparencyTemp);
                            } else {
                                $watermarkFontTransparency = '100';
                            }
							if($request->hasfile('wmfile')) {
                                try {
                                    $randomizeImageExtension = pathinfo($watermarkImage->getClientOriginalName(), PATHINFO_EXTENSION);
                                    $watermarkImage->storeAs('public/upload-pdf', $randomizeImageFileName.'.'.$randomizeImageExtension);
                                    $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setEncryption(true);
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $wmImage = $ilovepdfTask->addElementFile(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$randomizeImageFileName.'.'.$randomizeImageExtension));
                                    $ilovepdfTask->setMode("image");
                                    $ilovepdfTask->setImageFile($wmImage);
                                    $ilovepdfTask->setTransparency(intval($watermarkFontTransparency));
                                    $ilovepdfTask->setRotation($watermarkRotation);
                                    $ilovepdfTask->setLayer($watermarkLayoutStyle);
                                    $ilovepdfTask->setPages($watermarkPage);
                                    $ilovepdfTask->setMosaic($isMosaic);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                } catch (StartException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (AuthException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (UploadException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (DownloadException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (TaskException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (PathException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (\Exception $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
							} else {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                            'errReason' => 'Image file not found on the server !',
                                            'errApiReason' => null
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
							}
						} else if ($watermarkStyle == "text") {
                            if(isset($_POST['watermarkPageText']))
                            {
                                $watermarkInputPage = $request->post('watermarkPageText');
                                if (is_string($watermarkInputPage)) {
                                    $watermarkPage = strtolower($watermarkInputPage);
                                } else {
                                    $watermarkPage = $watermarkInputPage;
                                }
                            } else {
                                $watermarkPage = 'all';
                            }
                            if(isset($_POST['watermarkFontTextTransparency']))
                            {
                                $watermarkFontTransparencyTemp = $request->post('watermarkFontTextTransparency');
                                $watermarkFontTransparency = intval($watermarkFontTransparencyTemp);
                            } else {
                                $watermarkFontTransparency = '100';
                            }
                            if ($watermarkText != '') {
                                try {
                                    $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption($pdfEncKey);
                                    $ilovepdfTask->setEncryptKey($pdfEncKey);
                                    $ilovepdfTask->setEncryption(true);
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $ilovepdfTask->setMode("text");
                                    $ilovepdfTask->setText($watermarkText);
                                    $ilovepdfTask->setPages($watermarkPage);
                                    $ilovepdfTask->setVerticalPosition("middle");
                                    $ilovepdfTask->setRotation($watermarkRotation);
                                    $ilovepdfTask->setFontColor('#000000');
                                    $ilovepdfTask->setFontFamily($watermarkFontFamily);
                                    $ilovepdfTask->setFontStyle($watermarkFontStyle);
                                    $ilovepdfTask->setFontSize($watermarkFontSize);
                                    $ilovepdfTask->setTransparency($watermarkFontTransparency);
                                    $ilovepdfTask->setLayer($watermarkLayoutStyle);
                                    $ilovepdfTask->setMosaic($isMosaic);
                                    $ilovepdfTask->setOutputFileName($pdfNameWithoutExtention);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                } catch (StartException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (AuthException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (UploadException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (ProcessException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (DownloadException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (TaskException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                                'errApiReason' => null
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (PathException $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => $e->getMessage()
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                                'errApiReason' => null
                                        ]);
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                } catch (\Exception $e) {
                                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                    $duration = $end->diff($startProc);
                                    try {
                                        DB::table('appLogs')->insert([
                                            'processId' => $uuid,
                                            'errReason' => null,
                                            'errApiReason' => null
                                        ]);
                                        DB::table('pdfWatermark')->insert([
                                            'fileName' => $pdfName,
                                            'fileSize' => $newFileSize,
                                            'watermarkFontFamily' => $watermarkFontFamily,
                                            'watermarkFontStyle' => $watermarkFontStyle,
                                            'watermarkFontSize' => $watermarkFontSize,
                                            'watermarkFontTransparency' => $watermarkFontTransparency,
                                            'watermarkImage' => null,
                                            'watermarkLayout' => $watermarkLayoutStyle,
                                            'watermarkMosaic' => $isMosaicDB,
                                            'watermarkRotation' => $watermarkRotation,
                                            'watermarkStyle' => $watermarkStyle,
                                            'watermarkText' => $watermarkText,
                                            'watermarkPage' => $watermarkPage,
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
                                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                    } catch (QueryException $ex) {
                                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                    } catch (\Exception $e) {
                                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                    }
                                }
                            } else {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => null,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                            'errReason' => 'Watermark text can not empty !',
                                            'errApiReason' => $e->getMessage()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
						}
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
						    $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            if ($randomizeImageFileName == 'null') {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => null,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>$download_pdf,
                                    ]);
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            } else {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                    return redirect()->back()->with([
                                        "stats" => "scs",
                                        "res"=>$download_pdf,
                                    ]);
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        } else {
                            if ($randomizeImageFileName == 'null') {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => null,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            } else {
                                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfWatermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => $randomizeImageFileName.'.'.$randomizeImageExtension,
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                                }
                            }
                        }
					} else {
                        $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                        $duration = $end->diff($startProc);
                        try {
                            DB::table('appLogs')->insert([
                                'processId' => $uuid,
                                'errReason' => null,
                                'errApiReason' => null
                            ]);
                            DB::table('pdfWatermark')->insert([
                                'fileName' => null,
                                'fileSize' => null,
                                'watermarkFontFamily' => null,
                                'watermarkFontStyle' => null,
                                'watermarkFontSize' => null,
                                'watermarkFontTransparency' => null,
                                'watermarkImage' => null,
                                'watermarkLayout' => null,
                                'watermarkMosaic' => null,
                                'watermarkRotation' => null,
                                'watermarkStyle' => null,
                                'watermarkText' => null,
                                'watermarkPage' => null,
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
                            return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        } catch (\Exception $e) {
                            return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                        }
					}
				} else {
                    $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                    $duration = $end->diff($startProc);
                    try {
                        DB::table('appLogs')->insert([
                            'processId' => $uuid,
                            'errReason' => null,
                            'errApiReason' => null
                        ]);
                        DB::table('pdfWatermark')->insert([
                            'fileName' => null,
                            'fileSize' => null,
                            'watermarkFontFamily' => null,
                            'watermarkFontStyle' => null,
                            'watermarkFontSize' => null,
                            'watermarkFontTransparency' => null,
                            'watermarkImage' => null,
                            'watermarkLayout' => null,
                            'watermarkMosaic' => null,
                            'watermarkRotation' => null,
                            'watermarkStyle' => null,
                            'watermarkText' => null,
                            'watermarkPage' => null,
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
                        return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                    } catch (\Exception $e) {
                        return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                    }
				}
			} else {
                $end =  Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                $duration = $end->diff($startProc);
                try {
                    DB::table('appLogs')->insert([
                        'processId' => $uuid,
                        'errReason' => null,
                        'errApiReason' => null
                    ]);
                    DB::table('pdfWatermark')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'watermarkFontFamily' => null,
                        'watermarkFontStyle' => null,
                        'watermarkFontSize' => null,
                        'watermarkFontTransparency' => null,
                        'watermarkImage' => null,
                        'watermarkLayout' => null,
                        'watermarkMosaic' => null,
                        'watermarkRotation' => null,
                        'watermarkStyle' => null,
                        'watermarkText' => null,
                        'watermarkPage' => null,
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
                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    return redirect()->back()->withErrors(['error'=>'Eloquent transaction error !', 'processId'=>$uuid])->withInput();
                }
			}
		}
    }
}
