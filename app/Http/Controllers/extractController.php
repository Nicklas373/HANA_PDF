<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use App\Models\extract_pdf;

class extractController extends Controller
{
    public function extract(){
        return view('extract');
    }

    public function pdf_extract(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
		]);
 
		if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
			$file = $request->file('file');
			
			$pdfStartPages = 1;
			$pdfTotalPages = $this->getPDFPages($file);
			while($pdfStartPages <= intval($pdfTotalPages))
			{
				$pdfArrayPages[] = $pdfStartPages;
				$pdfStartPages += 1;
			}
			$pdfNewRanges = implode(', ', $pdfArrayPages);

			$pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = 'temp';
			$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');
			$file->move($pdfUpload_Location,$file->getClientOriginalName());
			$fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newCustomPage = "1 -".$pdfTotalPages;
			$newFileSize = $this->convert($fileSize, "MB");

			extract_pdf::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
                'customPage' => $newCustomPage,
				'hostName' => $hostName,
                'mergePDF' => "false"
			]);

			$ilovepdf = new Ilovepdf('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$ilovepdfTask = $ilovepdf->newTask('split');
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setRanges($pdfNewRanges);
			$ilovepdfTask->setMergeAfter(false);
			$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
			$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';
			
			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to split !')->withInput();
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