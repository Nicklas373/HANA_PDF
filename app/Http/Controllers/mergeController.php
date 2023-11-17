<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Helpers\AppHelper;
use App\Models\merge_pdf;
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
            return redirect()->back()->withErrors(['error'=>$validator->messages(), 'processId'=>$uuid])->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if ($request->hasfile('file')) {
                        foreach ($request->file('file') as $file) {
                            $str = rand();
                            $randomizePdfFileName = md5($str);
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
                        return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
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
                            $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
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
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
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
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
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
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
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
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
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
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
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
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
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
                        } catch (\Exception $e) {
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

                            DB::table('pdf_merge')->insert([
                                'processId' => $uuid,
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'result' => true,
                                'err_reason' => null,
                                'err_api_reason' => null,
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
                        } else {
                            DB::table('pdf_merge')->insert([
                                'processId' => $uuid,
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'result' => false,
                                'err_reason' => 'Failed to download file from iLovePDF API !',
                                'err_api_reason' => null,
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'PDF Merged failed !', 'processId'=>$uuid])->withInput();
                        }
					} else {
                        DB::table('pdf_merge')->insert([
                            'processId' => $uuid,
                            'fileName' => $fileNameArray,
                            'fileSize' => $fileSizeInMB,
                            'result' => false,
                            'err_reason' => 'PDF failed to upload !',
                            'err_api_reason' => null,
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'processId'=>$uuid])->withInput();
					}
				} else {
					return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !', 'processId'=>$uuid])->withInput();
				}
			} else {
				return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !', 'processId'=>$uuid])->withInput();
			}
        }
    }
}
