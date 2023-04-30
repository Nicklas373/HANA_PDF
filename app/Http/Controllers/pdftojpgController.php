<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_jpg;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\PdfjpgTask;

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
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
    
            pdf_jpg::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
				'hostName' => $hostName
			]);

            $ilovepdfTask = new PdfjpgTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
			$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setOutputFileName($file->getClientOriginalName());
            $ilovepdfTask->setPackagedFilename($pdfFilename['filename']);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$pdfFilename['filename'].'.zip';

			if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
				unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
			}
			
			if (file_exists($download_pdf)) {
				if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
					unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
				}
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to convert !')->withInput();
			}
        }
    }
}