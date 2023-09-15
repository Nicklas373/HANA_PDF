<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\compression_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Spatie\PdfToImage\Pdf;

class compressController extends Controller
{
	public function pdf_init(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);

		if($validator->fails()) {
			return redirect('compress')->withErrors($validator->messages())->withInput();
		} else {
            $uuid = AppHelper::Instance()->get_guid();
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('PDF_THUMBNAIL'));
							if (file_exists(env('PDF_THUMBNAIL'))) {
								$thumbnail = file(env('PDF_THUMBNAIL').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('compress')->with('upload',env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !', 'uuid'=>$uuid])->withInput();
							}
						} else {
							return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'uuid'=>$uuid])->withInput();
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
					}
				} else if ($request->post('formAction') == "compress") {
					if(isset($_POST['fileAlt'])) {
						if(isset($_POST['compMethod']))
						{
							$compMethod = $request->post('compMethod');
						} else {
							$compMethod = "recommended";
						}

                        $str = rand();
                        $randomizeFileName = md5($str);
						$file = $request->post('fileAlt');
						$pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfUpload_Location = env('PDF_UPLOAD');
						$pdfName = basename($file);
						$fileSize = filesize($file);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        rename($file, $pdfUpload_Location.'/'.$randomizeFileName.'.pdf');
                        $newRandomizeFile = $pdfUpload_Location.'/'.$randomizeFileName.'.pdf';

                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('compress');
                            $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                            $pdfFile = $ilovepdfTask->addFile($newRandomizeFile);
                            $ilovepdfTask->setOutputFileName($randomizeFileName);
                            $ilovepdfTask->setCompressionLevel($compMethod);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download($pdfProcessed_Location);
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                           DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                           DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                           DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                          DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                           DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                           DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        }

                        if (is_file($newRandomizeFile)) {
                            unlink($newRandomizeFile);
                        }

                        if (file_exists($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf')) {
                            rename($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf', $pdfProcessed_Location.'/'.$pdfName);
                            $download_pdf = $pdfProcessed_Location.'/'.$pdfName;
                            $compFileSize = filesize($download_pdf);
                            $newCompFileSize = AppHelper::instance()->convert($compFileSize, "MB");

                            DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => $newCompFileSize,
                                'compMethod' => $compMethod,
                                'hostName' => $hostName,
                                'result' => true,
                                'err_reason' => null,
                                'err_api_reason' => null,
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
							return redirect()->back()->with([
                                "stats" => "scs",
                                "res"=>$download_pdf,
                                "curFileSize"=>$newFileSize,
                                "newFileSize"=>$newCompFileSize,
                                "compMethod"=>$compMethod
                            ]);
                        } else {
                            DB::table('compression_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'compFileSize' => null,
                                'compMethod' => $compMethod,
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
