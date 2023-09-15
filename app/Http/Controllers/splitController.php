<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\extract_pdf;
use App\Models\split_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;
use Spatie\PdfToImage\Pdf;

class splitController extends Controller
{
    public function pdf_split(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);

		if($validator->fails()) {
            return redirect('split')->withErrors($validator->messages())->withInput();
        } else {
            $uuid = AppHelper::Instance()->get_guid();
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = public_path('upload-pdf');
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
								$thumbnail = file(env('PDF_THUMBNAIL').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('split')->with('upload',env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !', 'uuid'=>$uuid])->withInput();
							}
						} else {
                            return redirect()->back()->withErrors(['error'=>'PDF file not found on the server !', 'uuid'=>$uuid])->withInput();
						}
					} else {
                        return redirect()->back()->withErrors(['error'=>'PDF failed to upload !', 'uuid'=>$uuid])->withInput();
					}
				} else if ($request->post('formAction') == "split") {
					if(isset($_POST['fileAlt'])) {
						$file = $request->post('fileAlt');

						if(isset($_POST['fromPage']))
						{
							$fromPage = $request->post('fromPage');
						} else {
							$fromPage = '';
						}

						if(isset($_POST['toPage']))
						{
							$toPage = $request->post('toPage');
						} else {
							$toPage = '';
						}

						if(isset($_POST['mergePDF']))
						{
							$tempPDF = $request->post('mergePDF');
							$tempCompare = $tempPDF ? 'true' : 'false';
							$mergeDBpdf = "true";
							$mergePDF = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						} else {
							$tempCompare = false ? 'true' : 'false';
							$mergeDBpdf = "false";
							$mergePDF = filter_var($tempCompare, FILTER_VALIDATE_BOOLEAN);
						}

						if(isset($_POST['fixedPage']))
						{
							$fixedPage = $request->post('fixedPage');
						} else {
							$fixedPage = '';
						}

						if(isset($_POST['customPage']))
						{
							$customPage = $request->post('customPage');
						} else {
							$customPage = '';
						}

						if ($fromPage != ''){
							$pdfTotalPages = AppHelper::instance()->count($file);
							if ($toPage > $pdfTotalPages) {
								return redirect()->back()->withErrors([
                                    'error'=>'ToPage selected value has more than total PDF pages ! (total pages: '.$pdfTotalPages.')',
                                    'uuid'=>$uuid
                                    ])->withInput();
                            } else if ($fromPage > $pdfTotalPages) {
                                return redirect()->back()->withErrors([
                                    'error'=>'FromPage selected value has more than total PDF pages ! (total pages: '.$pdfTotalPages.')',
                                    'uuid'=>$uuid
                                    ])->withInput();
                            } else if ($fromPage > $toPage) {
								return redirect()->back()->withErrors([
                                    'error'=>'FirstPage value has more than ToPage value !',
                                    'uuid'=>$uuid
                                    ])->withInput();
							} else {
								if ($mergeDBpdf == "true") {
									$fixedPageRanges = $fromPage.'-'.$toPage;
								} else if ($mergeDBpdf == "false") {
									$pdfStartPages = $fromPage;
									$pdfTotalPages = $toPage;
									while($pdfStartPages <= intval($pdfTotalPages))
									{
										$pdfArrayPages[] = $pdfStartPages;
										$pdfStartPages += 1;
									}
									$fixedPageRanges = implode(', ', $pdfArrayPages);
								}
							}
						} else {
							$fixedPageRanges = $customPage;
						};

                        $str = rand();
                        $randomizeFileName = md5($str);
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfName = basename($file);
						$pdfNameWithoutExtension = basename($file, '.pdf');
						$fileSize = filesize($pdfUpload_Location.'/'.basename($file));
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
						$hostName = AppHelper::instance()->getUserIpAddr();
                        rename($file, $pdfUpload_Location.'/'.$randomizeFileName.'.pdf');
                        $newRandomizeFile = $pdfUpload_Location.'/'.$randomizeFileName.'.pdf';

                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('split');
                            $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                            $pdfFile = $ilovepdfTask->addFile($newRandomizeFile);
                            $ilovepdfTask->setRanges($fixedPageRanges);
                            $ilovepdfTask->setMergeAfter($mergePDF);
                            $ilovepdfTask->setPackagedFilename($randomizeFileName);
                            $ilovepdfTask->setOutputFileName($randomizeFileName);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download($pdfProcessed_Location);
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
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

                        if (file_exists($pdfProcessed_Location.'/'.$randomizeFileName.'.zip')) {
                            rename($pdfProcessed_Location.'/'.$randomizeFileName.'.zip', $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';

                            if (file_exists($download_pdf)) {
                                DB::table('split_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'hostName' => $hostName,
                                    'mergePDF' => $mergeDBpdf,
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
                                DB::table('split_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'hostName' => $hostName,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
								return redirect()->back()->withErrors(['error'=>'Failed to download file from iLovePDF API !', 'uuid'=>$uuid])->withInput();
							}
                        } else if (file_exists($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf')) {
                            rename($pdfProcessed_Location.'/'.$randomizeFileName.'.pdf', $pdfProcessed_Location.'/'.$pdfName);
                            $download_pdf = $pdfProcessed_Location.'/'.$pdfName;

                            if (file_exists($download_pdf)) {
								DB::table('split_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'hostName' => $hostName,
                                    'mergePDF' => $mergeDBpdf,
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
                                DB::table('split_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'hostName' => $hostName,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'Splitted file not found on the server !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
								return redirect()->back()->withErrors(['error'=>'Splitted file not found on the server !', 'uuid'=>$uuid])->withInput();
							}
                        } else {
                            DB::table('split_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'hostName' => $hostName,
                                'mergePDF' => $mergeDBpdf,
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
				} else if ($request->post('formAction') == "extract") {
                    if(isset($_POST['fileAlt'])) {
                        $file = $request->post('fileAlt');

                        if(isset($_POST['customPage']))
						{
							$customPage = $request->post('customPage');
						} else {
							$customPage = '';
						}

                        $pdfNewRanges = $customPage;

                        $str = rand();
                        $randomizeFileName = md5($str);
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfName = basename($file);
						$pdfNameWithoutExtension = basename($pdfName, '.pdf');
                        $fileSize = filesize($pdfUpload_Location.'/'.$pdfName);
                        $hostName = AppHelper::instance()->getUserIpAddr();
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        rename($file, $pdfUpload_Location.'/'.$randomizeFileName.'.pdf');
                        $newRandomizeFile = $pdfUpload_Location.'/'.$randomizeFileName.'.pdf';

                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('split');
                            $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                            $pdfFile = $ilovepdfTask->addFile($newRandomizeFile);
                            $ilovepdfTask->setRanges($pdfNewRanges);
                            $ilovepdfTask->setMergeAfter(false);
                            $ilovepdfTask->setPackagedFilename($randomizeFileName);
                            $ilovepdfTask->setOutputFileName($randomizeFileName);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download($pdfProcessed_Location);
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                'err_api_reason' => $e->getMessage(),
                                'uuid' => $uuid,
                                'created_at' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
                        } catch (\Exception $e) {
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
                                'hostName' => $hostName,
                                'result' => false,
                                'err_reason' => 'iLovePDF API Error ! Catch on Exception',
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

                            if (file_exists($download_pdf)) {
                                DB::table('extract_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'customPage' => $customPage,
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
                                ]);
                            } else {
                                DB::table('extract_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'customPage' => $customPage,
                                    'hostName' => $hostName,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => null,
                                    'uuid' => $uuid,
                                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'Failed to download file from iLovePDF API !', 'uuid'=>$uuid])->withInput();
                            }
                        } else if (file_exists($pdfProcessed_Location.'/'.$randomizeFileName.'.zip')) {
                            rename($pdfProcessed_Location.'/'.$randomizeFileName.'.zip', $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';

                            if (file_exists($download_pdf)) {
                                DB::table('extract_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'customPage' => $customPage,
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
                                ]);
                            } else {
                                DB::table('extract_pdfs')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'customPage' => $customPage,
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
                            DB::table('extract_pdfs')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'customPage' => $customPage,
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
				return redirect()->back()->withErrors(['error'=>'ERROR_OUT_OF_BOUND !', 'uuid'=>$uuid])->withInput();
			}
		}
    }
}
