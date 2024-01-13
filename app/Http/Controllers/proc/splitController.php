<?php

namespace App\Http\Controllers;

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

class splitController extends Controller
{
    public function split(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25600',
			'fileAlt' => ''
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
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','PDF Conversion failed !',$validator->messages());
                return redirect()->back()->withErrors([
                    'error'=>'PDF split failed!',
                    'processId'=>$uuid,
                    'titleMessage'=>'PDF page has failed to split !',
                ])->withInput();
            } catch (QueryException $ex) {
                NotificationHelper::Instance()->sendErrNotify('','', $uuid, 'FAIL','Database connection error !',$ex->messages());
                return redirect()->back()->withErrors([
                    'error'=>'Database connection error !',
                    'processId'=>'null',
                    'titleMessage'=>'PDF page has failed to split !',
                ])->withInput();
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
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $randomizePdfFileName.'.pdf',
                                    'fileSize' => $fileSize,
                                    'fromPage' => null,
                                    'toPage' => null,
                                    'customPage' => null,
                                    'fixedPage' => null,
                                    'fixedPageRange' => null,
                                    'mergePDF' => null,
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
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $fileSize, $uuid, 'FAIL', 'PDF file not found on the server !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $fileSize, $uuid, 'FAIL', 'Database connection error !', 'null');
								return redirect()->back()->withErrors([
									'error'=>'Database connection error !',
									'processId'=>'null',
									'titleMessage'=>'PDF page has failed to split !',
								])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($randomizePdfFileName.'.pdf', $fileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
								return redirect()->back()->withErrors([
									'error'=>'Eloquent transaction error !',
									'processId'=>'null',
									'titleMessage'=>'PDF page has failed to split !',
								])->withInput();
                            }
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
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => null,
                                        'fixedPage' => null,
                                        'fixedPageRange' => null,
                                        'mergePDF' => null,
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
                                            'errReason' => 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                            'errApiReason' => null
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Last page has more page than total PDF page ! (total page: '.$pdfTotalPages.')', 'null');
                                    return redirect()->back()->withErrors([
                                        'error'=>'PDF split failed!',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Database transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Eloquent transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
                                }
                            } else if ($fromPage > $pdfTotalPages) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => null,
                                        'fixedPage' => null,
                                        'fixedPageRange' => null,
                                        'mergePDF' => null,
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
                                            'errReason' => 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')',
                                            'errApiReason' => null
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'First page has more page than total PDF page ! (total page: '.$pdfTotalPages.')', 'null');
                                    return redirect()->back()->withErrors([
                                        'error'=>'PDF split failed!',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Database transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Eloquent transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
                                }
                            } else if ($fromPage > $toPage) {
                                $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                                $duration = $end->diff($startProc);
                                try {
                                    DB::table('appLogs')->insert([
                                        'processId' => $uuid,
                                        'errReason' => null,
                                        'errApiReason' => null
                                    ]);
                                    DB::table('pdfSplit')->insert([
                                        'fileName' => $pdfName,
                                        'fileSize' => $newFileSize,
                                        'fromPage' => $fromPage,
                                        'toPage' => $toPage,
                                        'customPage' => null,
                                        'fixedPage' => null,
                                        'fixedPageRange' => null,
                                        'mergePDF' => null,
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
                                            'errReason' => 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')',
                                            'errApiReason' => null
                                    ]);
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'First Page has more page than last page ! (total page: '.$pdfTotalPages.')', 'null');
                                    return redirect()->back()->withErrors([
                                        'error'=>'PDF split failed!',
                                        'processId'=>$uuid,
                                        'titleMessage'=>'PDF page has failed to split !'
                                    ])->withInput();
                                } catch (QueryException $ex) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Database transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
                                } catch (\Exception $e) {
                                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
									return redirect()->back()->withErrors([
										'error'=>'Eloquent transaction error !',
										'processId'=>'null',
										'titleMessage'=>'PDF page has failed to split !',
									])->withInput();
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
                        } catch (StartException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on CatchException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            }
                        }
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                    "titleMessage"=>"PDF page has successfully splitted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                                DB::table('pdfSplit')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'fromPage' => $fromPage,
                                    'toPage' => $toPage,
                                    'customPage' => $customPage,
                                    'fixedPage' => $fixedPage,
                                    'fixedPageRange' => $fixedPageRanges,
                                    'mergePDF' => $mergeDBpdf,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Failed to download file from iLovePDF API !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF split failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to split !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to split !',
                                ])->withInput();
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
                            DB::table('pdfSplit')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'fromPage' => $fromPage,
                                'toPage' => $toPage,
                                'customPage' => $customPage,
                                'fixedPage' => $fixedPage,
                                'fixedPageRange' => $fixedPageRanges,
                                'mergePDF' => $mergeDBpdf,
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
                                    'errReason' => 'PDF page has failed to split !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'PDF page has failed to split !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'PDF split failed!',
                                'processId'=>$uuid,
                                'titleMessage'=>'PDF page has failed to split !'
                            ])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'Database transaction error !',
                                'processId'=>'null',
                                'titleMessage'=>'PDF page has failed to split !',
                            ])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'Eloquent transaction error !',
                                'processId'=>'null',
                                'titleMessage'=>'PDF page has failed to split !',
                            ])->withInput();
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
                        } catch (StartException $e) {
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on AuthException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on UploadException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on ProcessException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on DownloadException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on TaskException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on PathException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on Exception', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            }
                        }
                        if (file_exists($pdfNewPath)) {
                            unlink($pdfNewPath);
                        }
                        if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfName))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfName);
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfUpload_Location.'/'.$pdfNameWithoutExtension.'.pdf'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.pdf');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            }
                        } else if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip'))) {
                            $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$altPdfNameWithoutExtension.'.zip');
                            $end = Carbon::parse(AppHelper::instance()->getCurrentTimeZone());
                            $duration = $end->diff($startProc);
                            try {
                                DB::table('appLogs')->insert([
                                    'processId' => $uuid,
                                    'errReason' => null,
                                    'errApiReason' => null
                                ]);
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                    "titleMessage"=>"PDF page has successfully deleted !"
                                ]);
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                                DB::table('pdfDelete')->insert([
                                    'fileName' => $pdfName,
                                    'fileSize' => $newFileSize,
                                    'deletePage' => $customPage,
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
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'iLovePDF API Error !, Catch on StartException', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'PDF delete failed!',
                                    'processId'=>$uuid,
                                    'titleMessage'=>'PDF page has failed to delete !'
                                ])->withInput();
                            } catch (QueryException $ex) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Database transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
                            } catch (\Exception $e) {
                                NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                                return redirect()->back()->withErrors([
                                    'error'=>'Eloquent transaction error !',
                                    'processId'=>'null',
                                    'titleMessage'=>'PDF page has failed to delete !',
                                ])->withInput();
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
                            DB::table('pdfDelete')->insert([
                                'fileName' => $pdfName,
                                'fileSize' => $newFileSize,
                                'deletePage' => $customPage,
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
                                    'errReason' => 'PDF page has failed to delete !',
                                    'errApiReason' => null
                            ]);
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'PDF page has failed to delete !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'PDF delete failed!',
                                'processId'=>$uuid,
                                'titleMessage'=>'PDF page has failed to delete !'
                            ])->withInput();
                        } catch (QueryException $ex) {
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'Database transaction error !',
                                'processId'=>'null',
                                'titleMessage'=>'PDF page has failed to delete !',
                            ])->withInput();
                        } catch (\Exception $e) {
                            NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                            return redirect()->back()->withErrors([
                                'error'=>'Eloquent transaction error !',
                                'processId'=>'null',
                                'titleMessage'=>'PDF page has failed to delete !',
                            ])->withInput();
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
                        DB::table('pdfDelete')->insert([
                            'fileName' => null,
                            'fileSize' => null,
                            'deletePage' => null,
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
                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'INVALID_REQUEST_ERROR !', 'null');
                        return redirect()->back()->withErrors([
                            'error'=>'PDF split failed!',
                            'processId'=>$uuid,
                            'titleMessage'=>'PDF page has failed to split !'
                        ])->withInput();
                    } catch (QueryException $ex) {
                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                        return redirect()->back()->withErrors([
                            'error'=>'Database transaction error !',
                            'processId'=>'null',
                            'titleMessage'=>'PDF page has failed to split !',
                        ])->withInput();
                    } catch (\Exception $e) {
                        NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                        return redirect()->back()->withErrors([
                            'error'=>'Eloquent transaction error !',
                            'processId'=>'null',
                            'titleMessage'=>'PDF page has failed to split !',
                        ])->withInput();
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
                    DB::table('pdfDelete')->insert([
                        'fileName' => null,
                        'fileSize' => null,
                        'deletePage' => null,
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
                            'errReason' => 'ERROR_OUT_BOUND !',
                            'errApiReason' => null
                    ]);
                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'ERROR_OUT_BOUND !', 'null');
                    return redirect()->back()->withErrors([
                        'error'=>'PDF split failed!',
                        'processId'=>$uuid,
                        'titleMessage'=>'PDF page has failed to split !'
                    ])->withInput();
                } catch (QueryException $ex) {
                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Database transaction error !', 'null');
                    return redirect()->back()->withErrors([
                        'error'=>'Database transaction error !',
                        'processId'=>'null',
                        'titleMessage'=>'PDF page has failed to split !',
                    ])->withInput();
                } catch (\Exception $e) {
                    NotificationHelper::Instance()->sendErrNotify($pdfName, $newFileSize, $uuid, 'FAIL', 'Eloquent transaction error !', 'null');
                    return redirect()->back()->withErrors([
                        'error'=>'Eloquent transaction error !',
                        'processId'=>'null',
                        'titleMessage'=>'PDF page has failed to split !',
                    ])->withInput();
                }
			}
		}
    }
}
