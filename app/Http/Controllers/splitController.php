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
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
			if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = env('pdf_upload');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('pdf_thumbnail'));
							if (file_exists(env('pdf_thumbnail').'/1.png')) {
								$thumbnail = file(env('pdf_thumbnail').'/1.png');
								rename(env('pdf_thumbnail').'/1.png', env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect()->back()->with('upload','/'.env('pdf_thumbnail').'/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect()->back()->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect()->back()->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
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

						if (!empty($fromPage)){
							$pdfTotalPages = AppHelper::instance()->count($file);
							if ($toPage > $pdfTotalPages) {
								return redirect()->back()->withError('error',$file. 'Invalid page range')->withInput();
							} else if ($fromPage > $toPage) {
								return redirect()->back()->withError('error',$file. 'Invalid page range')->withInput();
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

						$pdfUpload_Location = 'upload-pdf';
						$pdfProcessed_Location = 'temp';
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

						$ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
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

						if(is_file($pdfUpload_Location.'/'.basename($file))) {
							unlink($pdfUpload_Location.'/'.basename($file));
						}

						if ($mergeDBpdf == "false") {
							if (file_exists($download_merge_pdf)) {
								return redirect()->back()->with('success',$download_merge_pdf);
							} else {
								return redirect()->back()->withError('error',' has failed to split !')->withInput();
							}
						} else if ($mergeDBpdf == "true") {
							if (file_exists($download_split_pdf)) {
								return redirect()->back()->with('success',$download_split_pdf);
							} else {
								return redirect()->back()->withError('error',' has failed to split !')->withInput();
							}
						}
					} else {
						return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "extract") {
					$file = $request->post('fileAlt');
			
					$pdfStartPages = 1;
					$pdfTotalPages = AppHelper::instance()->count($file);
					while($pdfStartPages <= intval($pdfTotalPages))
					{
						$pdfArrayPages[] = $pdfStartPages;
						$pdfStartPages += 1;
					}
					$pdfNewRanges = implode(', ', $pdfArrayPages);

					$pdfUpload_Location = 'upload-pdf';
					$pdfProcessed_Location = 'temp';
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

					$ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
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
					
					if(is_file($pdfUpload_Location.'/'.basename($file))) {
						unlink($pdfUpload_Location.'/'.basename($file));
					}
					
					if (file_exists($download_pdf)) {
						return redirect()->back()->with('success',$download_pdf);
					} else {
						return redirect()->back()->withError('error',' has failed to split !')->withInput();
					}
				}
			} else {
				return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
		}
    }
}