<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Helpers\AppHelper;
use App\Models\merge_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Spatie\PdfToImage\Pdf;

class mergeController extends Controller
{
    public function pdf_merge(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'file' => 'max:25000',
			'fileAlt' => '',
            'dropFile' => ''
        ]);

        $uuid = AppHelper::Instance()->get_guid();

        if($validator->fails()) {
            return redirect()->back()->withErrors(['error'=>$validator->messages(), 'uuid'=>$uuid])->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if ($request->hasfile('file')) {
                        foreach ($request->file('file') as $file) {
                            $filename = $file->getClientOriginalName();
						    $pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
                            $file->move(env('PDF_MERGE_TEMP'), $filename);
                            $pdf = new Pdf(env('PDF_MERGE_TEMP').'/'.$filename);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('PDF_THUMBNAIL'));
							if (file_exists(env('PDF_THUMBNAIL').'/1.png')) {
								$thumbnail = file(env('PDF_THUMBNAIL').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
                                $pdfResponse[] = env('PDF_MERGE_TEMP').'/'.$pdfNameWithoutExtension.'.pdf';
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !', 'uuid'=>$uuid])->withInput();
							}
                        }
                        return redirect()->back()->with('upload', implode(',',$pdfResponse));
                    } else {
                        return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
                    }
                } else if ($request->post('formAction') == "merge") {
					if(isset($_POST['fileAlt'])) {
                        if(isset($_POST['dropFile']))
						{
							$dropFile = array($request->post('dropFile'));
						} else {
							$dropFile = array();
						}
						$fileNameArray = 'public/'.$request->post('fileAlt');
						$fileName = basename('public/'.$request->post('fileAlt'));
                        $fileSizeArray = AppHelper::instance()->folderSize(env('PDF_MERGE_TEMP'));
                        $fileSizeInMB = AppHelper::instance()->convert($fileSizeArray, "MB");
                        $hostName = AppHelper::instance()->getUserIpAddr();
                        $pdfArray = scandir(env('PDF_MERGE_TEMP'));
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
                                    $pdfName = $ilovepdfTask->addFile($pdfPreProcessed_Location.'/'.$value);
                                    $arrayCount += 1;
                                }
                            }
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download($pdfProcessed_Location);
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Exception $e) {
							DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        }

                        $tempPDFfiles = glob($pdfPreProcessed_Location . '/*');
                        foreach($tempPDFfiles as $file){
                            if(is_file($file)) {
                                unlink($file);
                            }
                        }

                        if (file_exists($pdfProcessed_Location.'/merged.pdf')) {
                            $download_pdf = $pdfProcessed_Location.'/merged.pdf';

                            DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => true,
                                'err_reason' => null,
                                'err_api_reason' => null,
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
                        } else {
                            DB::table('merge_pdfs')->insert([
                                'fileName' => $fileNameArray,
                                'fileSize' => $fileSizeInMB,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'Failed to download file from iLovePDF API !',
                                'err_api_reason' => null,
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'Failed to download file from iLovePDF API !', 'uuid'=>$uuid])->withInput();
                        }
					} else {
                        DB::table('merge_pdfs')->insert([
                            'fileName' => $fileNameArray,
                            'fileSize' => $fileSizeInMB,
                            'hostName' => $hostName,
                            'result' => false,
                            'err_reason' => 'PDF failed to upload !',
                            'err_api_reason' => null,
                            'uuid' => $uuid,
                            'created_at' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
					}
				} else {
					return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !', 'uuid'=>$uuid])->withInput();
				}
			} else {
				return redirect()->back()->withErrors(['error'=>'REQUEST_ERROR_OUT_OF_BOUND !', 'uuid'=>$uuid])->withInput();
			}
        }
    }
}
