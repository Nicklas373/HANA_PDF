<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use App\Models\split_pdf;

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
				$pdfTotalPages = $this->getPDFPages($file);
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
					$pdfTotalPages = $this->getPDFPages($file);
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
			$newFileSize = $this->convert($fileSize, "MB");
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

			$ilovepdf = new Ilovepdf('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$ilovepdfTask = $ilovepdf->newTask('split');
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setRanges($fixedPageRanges);
			$ilovepdfTask->setMergeAfter($mergePDF);
			$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
			$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_merge_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';
			$download_split_pdf = $pdfProcessed_Location.'/'.$file->getClientOriginalName();

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

    function getPDFPages($document)
	{
    	$cmd = "C:\\xampp\\htdocs\\emsitpro-pdftools-tailwind\\public\\ext-library\\xpdf-tools-win-4.04\\bin64\\pdfinfo.exe";
    
    	exec("$cmd \"$document\"", $output);

    	// Iterate through lines
    	$pagecount = 0;
    	foreach($output as $op)
    	{
        	// Extract the number
        	if(preg_match("/Pages:\s*(\d+)/i", $op, $matches) === 1)
        	{
            	$pagecount = intval($matches[1]);
            	break;
        	}
    	}
    
    	return $pagecount;
	}

	function convert($size,$unit) 
	{
		if($unit == "KB")
		{
			return $fileSize = number_format(round($size / 1024,4), 2) . ' KB';	
		}
		if($unit == "MB")
		{
			return $fileSize = number_format(round($size / 1024 / 1024,4), 2) . ' MB';	
		}
		if($unit == "GB")
		{
			return $fileSize = number_format(round($size / 1024 / 1024 / 1024,4), 2) . ' GB';	
		}
	}
}