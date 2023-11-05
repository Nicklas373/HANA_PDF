<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\watermark_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Ilovepdf\WatermarkTask;

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

        $uuid = AppHelper::Instance()->get_guid();

		if($validator->fails()) {
            return redirect()->back()->withErrors(['error'=>$validator->messages(), 'uuid'=>$uuid])->withInput();
        } else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$str = rand();
						$pdfUpload_Location = env('PDF_UPLOAD');
                        $file = $request->file('file');
                        $randomizePdfFileName = md5($str);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
						$pdfFileName = $file->getClientOriginalName();
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
                                'pdfOriName' => $pdfFileName,
                            ]);
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
							$watermarkFontSize = '14';
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
						if(isset($_POST['watermarkFontTransparency']))
						{
							$watermarkFontTransparencyTemp = $request->post('watermarkFontTransparency');
							$watermarkFontTransparency = 100 - intval($watermarkFontTransparencyTemp);
						} else {
							$watermarkFontTransparency = '100';
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
						if(isset($_POST['watermarkPage']))
						{
							$watermarkPage = $request->post('watermarkPage');
						} else {
							$watermarkPage = 'all';
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
                        $file = $request->post('fileAlt');
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
						$pdfName = basename($file);
                        $pdfNameWithoutExtention = basename($pdfName, '.pdf');
                        $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
						$fileSize = filesize($pdfNewPath);
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
						if($watermarkStyle == "image") {
							if($request->hasfile('wmfile')) {
                                try {
                                    $watermarkImage->move(Storage::disk('local')->path('public/'.$pdfUpload_Location),$watermarkImage->getClientOriginalName());
                                    $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                                    $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                                    $wmImage = $ilovepdfTask->addElementFile(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$watermarkImage->getClientOriginalName()));
                                    $ilovepdfTask->setMode("image");
                                    $ilovepdfTask->setImageFile($wmImage);
                                    $ilovepdfTask->setTransparency(intval($watermarkFontTransparency));
                                    $ilovepdfTask->setRotation($watermarkRotation);
                                    $ilovepdfTask->setLayer($watermarkLayoutStyle);
                                    $ilovepdfTask->setPages($watermarkPage);
                                    $ilovepdfTask->setMosaic($isMosaic);
                                    $ilovepdfTask->execute();
                                    $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                                    $ilovepdfTask->deleteFile($pdfNewPath);
                                } catch (\Ilovepdf\Exceptions\StartException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                }
							} else {
                                DB::table('pdf_watermark')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
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
                                    'err_reason' => 'Image file not found on the server !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
								return redirect()->back()->withErrors(['error'=>'Image file not found on the server !', 'uuid'=>$uuid])->withInput();
							}
						} else if ($watermarkStyle == "text") {
                            if ($watermarkText != '') {
                                try {
                                    $ilovepdfTask = new WatermarkTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                                    $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
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
                                    $ilovepdfTask->deleteFile($pdfNewPath);
                                } catch (\Ilovepdf\Exceptions\StartException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\AuthException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\UploadException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Ilovepdf\Exceptions\PathException $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_watermark')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
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
                                    return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
                                }
                            } else {
                                DB::table('pdf_watermark')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
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
                                    'err_reason' => 'Watermark text can not empty !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'Watermark text can not empty !', 'uuid'=>$uuid])->withInput();
                            }
						}
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
						    $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            DB::table('pdf_watermark')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
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
                            DB::table('pdf_watermark')->insert([
                                'fileName' => basename($file),
                                'fileSize' => $newFileSize,
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
							return redirect()->back()->withErrors(['error'=>'PDF Watermark failed !', 'uuid'=>$uuid])->withInput();
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
