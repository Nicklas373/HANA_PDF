<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\delete_pdf;
use App\Models\init_pdf;
use App\Models\split_pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\Exceptions;

class splitController extends Controller
{
    public function pdf_split(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25600',
			'fileAlt' => ''
		]);

        $uuid = AppHelper::Instance()->get_guid();

		if($validator->fails()) {
            try {
                DB::table('pdf_init')->insert([
                    'processId' => $uuid,
                    'err_reason' => $validator->messages(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors([
                    'error'=>'File validation failed !',
                    'processId'=>$uuid,
                    'titleMessage'=>'PDF page has failed to split !',
                ])->withInput();
            } catch (QueryException $ex) {
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$str = rand(1000,10000000);
						$pdfUpload_Location = env('PDF_UPLOAD');
                        $file = $request->file('file');
                        $fileSize = filesize($file);
                        $randomizePdfFileName = 'pdf_split_'.substr(md5(uniqid($str)), 0, 8);
                        $randomizePdfPath = $pdfUpload_Location.'/'.$randomizePdfFileName.'.pdf';
						$pdfFileName = $file->getClientOriginalName();
                        $pdfTotalPages = AppHelper::instance()->count($file);
                        $file->storeAs('public/upload-pdf', $randomizePdfFileName.'.pdf');
						if (Storage::disk('local')->exists('public/'.$randomizePdfPath)) {
							return redirect()->back()->with([
                                'status' => true,
                                'pdfRndmName' => Storage::disk('local')->url(env('PDF_UPLOAD').'/'.$randomizePdfFileName.'.pdf'),
                                'pdfOriName' => $pdfFileName,
                                'pdfTotalPages' => $pdfTotalPages
                            ]);
						} else {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $fileSize,
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'PDF file not found on the server !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF file not found on the server !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
						}
					} else {
                        try {
                            DB::table('pdf_split')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'fromPage' => 'null',
                                'toPage' => 'null',
                                'customPage' => 'null',
                                'fixedPage' => 'null',
                                'fixedPageRange' => 'null',
                                'mergePDF' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF failed to upload !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors([
                                'error'=>'PDF failed to upload !',
                                'processId'=>$uuid,
                                'titleMessage'=>'PDF page has failed to split !'
                            ])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        }
					}
				} else if ($request->post('formAction') == "split") {
					if(isset($_POST['fileAlt'])) {
						$file = $request->post('fileAlt');

                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfEncKey = bin2hex(random_bytes(16));
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
						if(isset($_POST['customPageSplit']))
						{
							$customInputPage = $request->post('customPageSplit');
                            if (is_string($customInputPage)) {
                                $customPage = strtolower($customInputPage);
                            } else {
                                $customPage = $customInputPage;
                            }
						} else {
							$customPage = '';
						}
                        $pdfName = basename($file);
                        $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
                        if ($mergePDF) {
                            $pdfNameWithoutExtension = basename($file, '.pdf');
                        } else {
                            $pdfNameWithoutExtension = basename($pdfName, '.pdf').'_page';
                        }
                        $fileSize = filesize($pdfNewPath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
						if ($fromPage != ''){
							$pdfTotalPages = AppHelper::instance()->count($pdfNewPath);
							if ($toPage > $pdfTotalPages) {
                                try {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $fileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => $mergeDBpdf,
                                        'result' => false,
                                        'err_reason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                        'err_api_reason' => 'null',
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors([
                                        'error'=>'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'fromPage' => 'null',
                                        'toPage' => 'null',
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => 'null',
                                        'result' => false,
                                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                                }
                            } else if ($fromPage > $pdfTotalPages) {
                                try {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $fileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => $mergeDBpdf,
                                        'result' => false,
                                        'err_reason' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                        'err_api_reason' => 'null',
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors([
                                        'error'=>'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'fromPage' => 'null',
                                        'toPage' => 'null',
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => 'null',
                                        'result' => false,
                                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                                }
                            } else if ($fromPage > $toPage) {
                                try {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => $pdfName,
                                        'fileSize' => $fileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => $mergeDBpdf,
                                        'result' => false,
                                        'err_reason' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                        'err_api_reason' => 'null',
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors([
                                        'error'=>'First Page has more page than last page !',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                                } catch (\Exception $e) {
                                    DB::table('pdf_split')->insert([
                                        'processId' => $uuid,
                                        'fileName' => 'null',
                                        'fileSize' => 'null',
                                        'fromPage' => 'null',
                                        'toPage' => 'null',
                                        'customPage' => 'null',
                                        'fixedPage' => 'null',
                                        'fixedPageRange' => 'null',
                                        'mergePDF' => 'null',
                                        'result' => false,
                                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                        'err_api_reason' => $e->getMessage(),
                                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                    ]);
                                    return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                                }
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
                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('split');
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                            $ilovepdfTask->setRanges($fixedPageRanges);
                            $ilovepdfTask->setMergeAfter($mergePDF);
                            if ($mergePDF) {
                                $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                                $ilovepdfTask->setOutputFileName($pdfName);
                            } else {
                                $altPdfNameWithoutExtension = basename($pdfName, '.pdf');
                                $ilovepdfTask->setPackagedFilename($altPdfNameWithoutExtension);
                                $ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
                            }
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        }
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf');
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else {
                            try {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        }
					} else {
                        try {
                            DB::table('pdf_split')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'fromPage' => 'null',
                                'toPage' => 'null',
                                'customPage' => 'null',
                                'fixedPage' => 'null',
                                'fixedPageRange' => 'null',
                                'mergePDF' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF page has failed to split !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors([
                                'error'=>'PDF failed to upload !',
                                'processId'=>$uuid,
                                'titleMessage'=>'PDF page has failed to split !'
                            ])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        }
					}
				} else if ($request->post('formAction') == "delete") {
                    if(isset($_POST['fileAlt'])) {
                        $file = $request->post('fileAlt');

                        if(isset($_POST['customPageDelete']))
						{
							$customInputPage = $request->post('customPageDelete');
                            if (is_string($customInputPage)) {
                                $customPage = strtolower($customInputPage);
                            } else {
                                $customPage = $customInputPage;
                            }
						} else {
							$customPage = '';
						}
                        $pdfUpload_Location = env('PDF_UPLOAD');
                        $pdfProcessed_Location = env('PDF_DOWNLOAD');
                        $pdfEncKey = bin2hex(random_bytes(16));
                        $pdfNewRanges = $customPage;
                        $pdfName = basename($file);
                        $pdfNewPath = Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfName);
                        $pdfNameWithoutExtension = basename($pdfName, '.pdf');
                        $fileSize = filesize($pdfNewPath);
                        $newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                        try {
                            $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                            $ilovepdfTask = $ilovepdf->newTask('split');
                            $ilovepdfTask->setFileEncryption($pdfEncKey);
                            $ilovepdfTask->setEncryptKey($pdfEncKey);
                            $ilovepdfTask->setEncryption(true);
                            $pdfFile = $ilovepdfTask->addFile($pdfNewPath);
                            $ilovepdfTask->setRemovePages($pdfNewRanges);
                            $ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
                            $ilovepdfTask->setOutputFileName($pdfName);
                            $ilovepdfTask->execute();
                            $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
                        } catch (\Ilovepdf\Exceptions\StartException $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\AuthException $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\UploadException $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                                try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\TaskException $e) {
                                try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Ilovepdf\Exceptions\PathException $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } catch (\Exception $e) {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'iLovePDF API Error ! Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        }
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf');
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip');
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => true,
                                    'err_reason' => 'null',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->with([
                                    "stats" => "scs",
                                    "res"=>$download_pdf,
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        } else {
                            try {
                                DB::table('pdf_delete')->insert([
                                    'processId' => $uuid,
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
                                    'result' => false,
                                    'err_reason' => 'Failed to download file from iLovePDF API !',
                                    'err_api_reason' => 'null',
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete fail !',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                            } catch (\Exception $e) {
                                DB::table('pdf_split')->insert([
                                    'processId' => $uuid,
                                    'fileName' => 'null',
                                    'fileSize' => 'null',
                                    'fromPage' => 'null',
                                    'toPage' => 'null',
                                    'customPage' => 'null',
                                    'fixedPage' => 'null',
                                    'fixedPageRange' => 'null',
                                    'mergePDF' => 'null',
                                    'result' => false,
                                    'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                                    'err_api_reason' => $e->getMessage(),
                                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                                ]);
                                return redirect()->back()->withErrors(['error'=>'PDF split fail !', 'processId'=>$uuid])->withInput();
                            }
                        }
                    } else {
                        try {
                            DB::table('pdf_delete')->insert([
                                'processId' => $uuid,
                                'fileName' => 'null',
                                'fileSize' => 'null',
                                'deletePage' => 'null',
                                'result' => false,
                                'err_reason' => 'PDF page has failed to delete !',
                                'err_api_reason' => 'null',
                                'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                            ]);
                            return redirect()->back()->withErrors([
                                'error'=>'PDF failed to upload !',
                                'processId'=>$uuid,
                                'titleMessage'=>'PDF page has failed to delete !'
                            ])->withInput();
                        } catch (QueryException $ex) {
                            return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                        }
                    }
				} else {
                    try {
                        DB::table('pdf_delete')->insert([
                            'processId' => $uuid,
                            'fileName' => 'null',
                            'fileSize' => 'null',
                            'deletePage' => 'null',
                            'result' => false,
                            'err_reason' => 'INVALID_REQUEST_ERROR !',
                            'err_api_reason' => 'null',
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        return redirect()->back()->withErrors([
                            'error'=>'INVALID_REQUEST_ERROR !',
                            'processId'=>$uuid,
                            'titleMessage'=>'PDF process unknown error !'
                        ])->withInput();
                    } catch (QueryException $ex) {
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                    }
                }
			} else {
                try {
                    DB::table('pdf_delete')->insert([
                        'processId' => $uuid,
                        'fileName' => 'null',
                        'fileSize' => 'null',
                        'deletePage' => 'null',
                        'result' => false,
                        'err_reason' => 'ERROR_OUT_BOUND !',
                        'err_api_reason' => 'null',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors([
                        'error'=>'ERROR_OUT_BOUND !',
                        'processId'=>$uuid,
                        'titleMessage'=>'PDF process unknown error !'
                    ])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                }
			}
		}
    }
}
