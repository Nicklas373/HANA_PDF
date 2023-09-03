<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\extract_pdf;
use App\Models\split_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Spatie\PdfToImage\Pdf;

class splitController extends Controller
{
    public function split() {
        return view('split');
    }

    public function pdf_split(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
			'fileAlt' => ''
		]);

		if($validator->fails()) {
            return redirect('split')->withErrors($validator->messages())->withInput();
        } else {
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
								->saveImage(public_path('thumbnail'));
							if (file_exists(public_path('thumbnail').'/1.png')) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(public_path('thumbnail').'/1.png', public_path('thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('split')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withErrors(['error'=>'Thumbnail file not found !'])->withInput();
							}
						} else {
                            return redirect()->back()->withErrors(['error'=>'Thumbnail failed to generated !'])->withInput();
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !'])->withInput();
					}
				} else if ($request->post('formAction') == "split") {
					if(isset($_POST['fileAlt'])) {
						$file = 'public/'.$request->post('fileAlt');

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

						if (!empty($fromPage)){
							$pdfTotalPages = AppHelper::instance()->count($file);
							if ($toPage > $pdfTotalPages) {
								return redirect()->back()->withErrors(['error'=>'ToPage selected value has more than total PDF pages ! (total pages:'.$pdfTotalPages])->withInput();
							} else if ($fromPage > $toPage) {
								return redirect()->back()->withErrors(['error'=>'FirstPage value has more than ToPage value !'])->withInput();
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
							if(!empty($fixedPage)) {
								$pdfStartPages = 1;
								$pdfTotalPages = $fixedPage;
								while($pdfStartPages <= intval($pdfTotalPages))
								{
									$pdfArrayPages[] = $pdfStartPages;
									$pdfStartPages += 1;
								}
								$fixedPageRanges = implode(', ', $pdfArrayPages);
							} else if(!empty($customPage)) {
								$fixedPageRanges = '1-'.$customPage;
							}
						};

						$pdfUpload_Location = public_path('upload-pdf');
						$pdfProcessed_Location = public_path('temp');
						$pdfNameWithoutExtension = basename($file, '.pdf');
						$fileSize = filesize($pdfUpload_Location.'/'.basename($file));
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
						$hostName = AppHelper::instance()->getUserIpAddr();

						split_pdf::create([
							'fileName' => basename($file),
							'fileSize' => $newFileSize,
							'fromPage' => $fromPage,
							'toPage' => $toPage,
							'customPage' => $customPage,
							'fixedPage' => $fixedPage,
							'fixedPageRange' => $fixedPageRanges,
							'hostName' => $hostName,
							'mergePDF' => $mergeDBpdf
						]);

						$ilovepdf = new Ilovepdf('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
						$ilovepdfTask = $ilovepdf->newTask('split');
						$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
						$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.basename($file));
						$ilovepdfTask->setRanges($fixedPageRanges);
						$ilovepdfTask->setMergeAfter($mergePDF);
						$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
						$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
						$ilovepdfTask->execute();
						$ilovepdfTask->download($pdfProcessed_Location);

						$download_merge_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';
						$download_split_pdf = $pdfProcessed_Location.'/'.basename($file);

						if(is_file($file)) {
							unlink($file);
						}

						if ($mergeDBpdf == "true") {
							if (file_exists($download_merge_pdf)) {
								return redirect('split')->with('success','temp/'.basename($file));
							} else {
								return redirect()->back()->withErrors(['error'=>'Split process error !'])->withInput();
							}
						} else if ($mergeDBpdf == "false") {
							if (file_exists($download_split_pdf)) {
								return redirect('split')->with('success','temp/'.$pdfNameWithoutExtension.'.zip');
							} else {
								return redirect()->back()->withErrors(['error'=>'Split process error !'])->withInput();
							}
						}
					} else {
						return redirect()->back()->withErrors(['error'=>'PDF failed to upload !'])->withInput();
					}
				} else if ($request->post('formAction') == "extract") {
					$file = 'public/'.$request->post('fileAlt');

					$pdfStartPages = 1;
					$pdfTotalPages = AppHelper::instance()->count($file);
					while($pdfStartPages <= intval($pdfTotalPages))
					{
						$pdfArrayPages[] = $pdfStartPages;
						$pdfStartPages += 1;
					}
					$pdfNewRanges = implode(', ', $pdfArrayPages);

					$pdfUpload_Location = public_path('upload-pdf');
					$pdfProcessed_Location = public_path('temp');
					$pdfNameWithoutExtension = basename($file, '.pdf');
					$fileSize = filesize($pdfUpload_Location.'/'.basename($file));
					$hostName = AppHelper::instance()->getUserIpAddr();
					$newCustomPage = "1 -".$pdfTotalPages;
					$newFileSize = AppHelper::instance()->convert($fileSize, "MB");

					extract_pdf::create([
						'fileName' => basename($file),
						'fileSize' => $newFileSize,
						'customPage' => $newCustomPage,
						'hostName' => $hostName,
						'mergePDF' => "false"
					]);

					$ilovepdf = new Ilovepdf('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
					$ilovepdfTask = $ilovepdf->newTask('split');
					$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
					$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.basename($file));
					$ilovepdfTask->setRanges($pdfNewRanges);
					$ilovepdfTask->setMergeAfter(false);
					$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
					$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
					$ilovepdfTask->execute();
					$ilovepdfTask->download($pdfProcessed_Location);

					$download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';

					if(is_file($file)) {
						unlink($file);
					}

					if (file_exists($download_pdf)) {
						return redirect('split')->with('success','temp/'.$pdfNameWithoutExtension.'.zip');
					} else {
						return redirect()->back()->withErrors(['error'=>'Extract process error !'])->withInput();
					}
				}
			} else {
				return redirect()->back()->withErrors(['error'=>'INVALID_REQUEST_ERROR !'])->withInput();
			}
		}
    }
}
