<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Helpers\AppHelper;
use App\Models\init_pdf;
use App\Models\merge_pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;

class mergeController extends Controller
{
    public function pdf_merge(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'file' => 'max:25600',
			'fileAlt' => '',
            'dropFile' => ''
        ]);

        $uuid = AppHelper::Instance()->get_guid();

        if($validator->fails()) {
            try {
                DB::table('pdf_init')->insert([
                    'processId' => $uuid,
                    'err_reason' => $validator->messages(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>$validator->messages(), 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
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
                        try {
                            DB::table('pdf_merge')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF failed to upload !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
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
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
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
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        } else {
                            try {
                                DB::table('pdf_merge')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $fileNameArray,
                                    'fileSize' => $fileSizeInMB,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            }
                        }
					} else {
                        try {
                            DB::table('pdf_merge')->insert([
                                'processId' => $uuid,
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'result' => false,
                                'err_reason' => 'PDF failed to upload !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        }
					}
				} else {
                    try {
                        DB::table('pdf_merge')->insert([
                            'processId' => $uuid,
                            'fileName' => 'null',
                            'fileSize' => 'null',
                            'result' => false,
                            'err_reason' => 'INVALID_REQUEST_ERROR !',
                            'err_api_reason' => 'null',
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        return redirect()->back()->withErrors(['error'=>'PDF process unknown error !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                    }
				}
			} else {
                try {
                    DB::table('pdf_merge')->insert([
                        'processId' => $uuid,
                        'fileName' => 'null',
                        'fileSize' => 'null',
                        'result' => false,
                        'err_reason' => 'REQUEST_ERROR_OUT_OF_BOUND !',
                        'err_api_reason' => 'null',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'PDF process unknown error !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                }
			}
        }
    }
}
