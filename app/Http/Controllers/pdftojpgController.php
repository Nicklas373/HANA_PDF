<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\PdfjpgTask;
use App\Models\pdf_jpg;

class pdftojpgController extends Controller
{
    public function image(){
		return view('pdftojpg');
	}

    public function pdf_image(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'required|mimes:pdf|max:25000',
		]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            $file = $request->file('file');
            $pdfUpload_Location = 'upload-pdf';
            $pdfProcessed_Location = 'temp';
            $file->move($pdfUpload_Location,$file->getClientOriginalName());
            $pdfFilename = pathinfo($pdfUpload_Location.'/'.$file->getClientOriginalName());
            $fileSize = filesize($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$hostName = gethostname();
			$newFileSize = $this->convert($fileSize, "MB");
    
            pdf_jpg::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            $ilovepdfTask = new PdfjpgTask('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setOutputFileName($file->getClientOriginalName());
            $ilovepdfTask->setPackagedFilename($pdfFilename['filename']);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$pdfFilename['filename'].'.zip';

			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to convert !')->withInput();
			}
        }
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