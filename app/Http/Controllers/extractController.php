<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\extract_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;

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
			$pdfTotalPages = AppHelper::instance()->count($file);
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
			$newFileSize = AppHelper::instance()->convert($fileSize, "MB");

			extract_pdf::create([
				'fileName' => $file->getClientOriginalName(),
				'fileSize' => $newFileSize,
                'customPage' => $newCustomPage,
				'hostName' => $hostName,
                'mergePDF' => "false"
			]);

			$ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
			$ilovepdfTask = $ilovepdf->newTask('split');
			$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
			$pdfFile = $ilovepdfTask->addFile($pdfUpload_Location.'/'.$file->getClientOriginalName());
			$ilovepdfTask->setRanges($pdfNewRanges);
			$ilovepdfTask->setMergeAfter(false);
			$ilovepdfTask->setPackagedFilename($pdfNameWithoutExtension);
			$ilovepdfTask->setOutputFileName($pdfNameWithoutExtension);
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.zip';
			
			if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
				unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
			}
			
			if (file_exists($download_pdf)) {
				return redirect()->back()->with('success',$download_pdf);
			} else {
				return redirect()->back()->withError('error',' has failed to split !')->withInput();
			}
		}
    }
}