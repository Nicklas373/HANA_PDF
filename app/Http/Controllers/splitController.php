<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\split_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

class splitController extends Controller
{
    public function split() {
        return view('split');
    }

    public function pdf_split(Request $request): RedirectResponse{
		$validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
		]);
 
		if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
			$file = $request->file('file');
			
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

			if (empty($fromPage) == false){
				$pdfTotalPages = AppHelper::instance()->count($file);
				if ($toPage > $pdfTotalPages) {
					return redirect()->back()->withError('error',$file->getClientOriginalName(). 'Invalid page range')->withInput();
				} else if ($fromPage > $toPage) {
					return redirect()->back()->withError('error',$file->getClientOriginalName(). 'Invalid page range')->withInput();
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
				if(empty($fixedPage) == false) {
					$pdfStartPages = $fixedPage;
					$pdfTotalPages = $this->count($file);
					while($pdfStartPages <= intval($pdfTotalPages))
					{
						$pdfArrayPages[] = $pdfStartPages;
						$pdfStartPages += 1;
					}
					$fixedPageRanges = implode(', ', $pdfArrayPages);
				} else if(empty($customPage) == false) {
					$fixedPageRanges = $customPage;
				}
			};

			$pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = 'temp';
			$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
			$file->move($pdfUpload_Location,$file->getClientOriginalName());
			$fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			$hostName = gethostname();

			split_pdf::create([
				'fileName' => $file->getClientOriginalName(),
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
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setRanges($fixedPageRanges);
			$ilovepdfTask->setMergeAfter($mergePDF);
			$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
			$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_merge_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';
			$download_split_pdf = $pdfProcessed_Location.'/'.$file->getClientOriginalName();

			if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
				unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
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
		}
    }
}