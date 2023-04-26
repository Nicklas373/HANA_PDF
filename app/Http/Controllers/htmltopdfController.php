<?php
 
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\HtmlpdfTask;
use App\Models\html_pdf;

class htmltopdfController extends Controller
{
    public function html() {
        return view('htmltopdf');
    }

    public function html_pdf(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
            'urlToPDF' => 'required',
        ]);

        if($validator->fails()) {
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            $pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = 'temp';
            $hostName = gethostname();

            html_pdf::create([
                'urlName' => $request->post('urlToPDF'),
                'hostName' => $hostName
            ]);

			$ilovepdfTask = new HtmlpdfTask('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');
			$pdfFile = $ilovepdfTask->addUrl($request->post('urlToPDF'));
			$ilovepdfTask->setOutputFileName('captured');
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = $pdfProcessed_Location.'/captured.pdf';

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

    function folderSize($dir)
    {
        $size = 0;

        foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $each) {
            $size += is_file($each) ? filesize($each) : folderSize($each);
        }

        return $size;
    }
}