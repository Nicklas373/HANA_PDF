<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\watermark_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Ilovepdf\WatermarkTask;
use Spatie\PdfToImage\Pdf;

class watermarkController extends Controller
{
	public function pdf_watermark(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => '',
			'wmfile' => 'mimes:jpg,png,jpeg|max:5000',
			'watermarkText' => '',
			'watermarkPage' => '',
			'wmType' => '',
		]);

		if($validator->fails()) {
            return redirect('watermark')->withErrors($validator->messages())->withInput();
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
							if (file_exists(env('PDF_THUMBNAIL').'/1.png')) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('watermark')->with('upload',env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !', 'uuid'=>$uuid])->withInput();
							}
						} else {
                            return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'uuid'=>$uuid])->withInput();
						}
					} else {
                        return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
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
							$watermarkFontFamily = $request->post('watermarkFontFamily');
						} else {
							$watermarkFontFamily = '';
						}

						if(isset($_POST['watermarkFontSize']))
						{
							$watermarkFontSize = $request->post('watermarkFontSize');
						} else {
							$watermarkFontSize = '';
						}

						if(isset($_POST['watermarkFontStyle']))
						{
							$watermarkFontStyle = $request->post('watermarkFontStyle');
						} else {
							$watermarkFontStyle = '';
						}

						if(isset($_POST['watermarkFontTransparency']))
						{
							$watermarkFontTransparencyTemp = $request->post('watermarkFontTransparency');
							$watermarkFontTransparency = 100 - intval($watermarkFontTransparencyTemp);
						} else {
							$watermarkFontTransparency = '';
						}

						if(isset($_POST['watermarkLayoutStyle']))
						{
							$watermarkLayoutStyle = $request->post('watermarkLayoutStyle');
						} else {
							$watermarkLayoutStyle = '';
						}

						if(isset($_POST['watermarkPage']))
						{
							$watermarkPage = $request->post('watermarkPage');
						} else {
							$watermarkPage = '';
						}

						if(isset($_POST['watermarkRotation']))
						{
							$watermarkRotation = $request->post('watermarkRotation');
						} else {
							$watermarkRotation = '';
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
                        $randomizeFileName = md5($str);
						$pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
						$file = $request->post('fileAlt');
						$pdfName = basename($file);
						$pdfNameWithoutExtension = basename($file, ".pdf");
						$fileSize = filesize(public_path($file));
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        rename($file, $pdfUpload_Location.'/'.$randomizeFileName.'.pdf');
                        $newRandomizeFile = $pdfUpload_Location.'/'.$randomizeFileName.'.pdf';

						if($watermarkStyle == "image") {
							if($request->hasfile('wmfile')) {
                                try {
                                    $watermarkImage = $request->file('wmfile');
                                    $watermarkImage->move($pdfUpload_Location,$watermarkImage->getClientOriginalName());
                                    $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfFile = $ilovepdfTask->addFile($newRandomizeFile);
                                    $wmImage = $ilovepdfTask->addElementFile($pdfUpload_Location.'/'.$watermarkImage->getClientOriginalName());
                                    $ilovepdfTask->setMode("image");
                                    $ilovepdfTask->setImageFile($wmImage);
                                    $ilovepdfTask->setTransparency(intval($watermarkFontTransparency));
                                    $ilovepdfTask->setRotation($watermarkRotation);
                                    $ilovepdfTask->setLayer($watermarkLayoutStyle);
                                    $ilovepdfTask->setMosaic($isMosaic);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download($pdfProcessed_Location);
                                } catch (\Ilovepdf\Exceptions\StartException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('watermark_pdfs')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'hostName' => $hostName,
                                        'watermarkFontFamily' => $watermarkFontFamily,
                                        'watermarkFontStyle' => $watermarkFontStyle,
                                        'watermarkFontSize' => $watermarkFontSize,
                                        'watermarkFontTransparency' => $watermarkFontTransparency,
                                        'watermarkImage' => basename($watermarkImage),
                                        'watermarkLayout' => $watermarkLayoutStyle,
                                        'watermarkMosaic' => $isMosaicDB,
                                        'watermarkRotation' => $watermarkRotation,
                                        'watermarkStyle' => $watermarkStyle,
                                        'watermarkText' => $watermarkText,
                                        'watermarkPage' => $watermarkPage,
                                        'result' => false,
                                        'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'uuid' => $uuid,
                                        'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                                }
							} else {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'PDF file not found on the server !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
								return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !'])->withInput();
							}
						} else if ($watermarkStyle == "text") {
                            try {
                                $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                $pdfFile = $ilovepdfTask->addFile($newRandomizeFile);
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
                                $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                                $ilovepdfTask->execute();
                                $ilovepdfTask->download($pdfProcessed_Location);
                            }						catch (\Ilovepdf\Exceptions\StartException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Ilovepdf\Exceptions\PathException $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            } catch (\Exception $e) {
                                DB::table('watermark_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'hostName' => $hostName,
                                    'watermarkFontFamily' => $watermarkFontFamily,
                                    'watermarkFontStyle' => $watermarkFontStyle,
                                    'watermarkFontSize' => $watermarkFontSize,
                                    'watermarkFontTransparency' => $watermarkFontTransparency,
                                    'watermarkImage' => basename($watermarkImage),
                                    'watermarkLayout' => $watermarkLayoutStyle,
                                    'watermarkMosaic' => $isMosaicDB,
                                    'watermarkRotation' => $watermarkRotation,
                                    'watermarkStyle' => $watermarkStyle,
                                    'watermarkText' => $watermarkText,
                                    'watermarkPage' => $watermarkPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                            }
						}

                        if(is_file($newRandomizeFile)) {
							unlink($newRandomizeFile);
						}

                        if (file_exists($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf')) {
                            rename($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf', $pdfProcessed_Location.'/'.$pdfName);
						    $download_pdf = $pdfProcessed_Location.'/'.$pdfName;

                            DB::table('watermark_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'hostName' => $hostName,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkFontTransparency,
                                'watermarkImage' => basename($watermarkImage),
                                'watermarkLayout' => $watermarkLayoutStyle,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkStyle,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
                                'result' => true,
                                'err_reason' => null,
                                'err_api_reason' => null,
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
							return redirect()->back()->with([
                                "stats" => "scs",
                                "res"=>$download_pdf,
                            ]);
                        } else {
                            DB::table('watermark_pdfs')->insert([
                                'fileName' => basename($file),
                                'fileSize' => $newFileSize,
                                'hostName' => $hostName,
                                'watermarkFontFamily' => $watermarkFontFamily,
                                'watermarkFontStyle' => $watermarkFontStyle,
                                'watermarkFontSize' => $watermarkFontSize,
                                'watermarkFontTransparency' => $watermarkFontTransparency,
                                'watermarkImage' => basename($watermarkImage),
                                'watermarkLayout' => $watermarkLayoutStyle,
                                'watermarkMosaic' => $isMosaicDB,
                                'watermarkRotation' => $watermarkRotation,
                                'watermarkStyle' => $watermarkStyle,
                                'watermarkText' => $watermarkText,
                                'watermarkPage' => $watermarkPage,
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
