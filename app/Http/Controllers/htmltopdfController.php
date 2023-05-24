<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\html_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\HtmlpdfTask;

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
            return redirect('htmltopdf')->withErrors($validator->messages())->withInput();
        } else {
            $pdfUpload_Location = 'upload-pdf';
			$pdfProcessed_Location = public_path('temp');
            $hostName = AppHelper::instance()->getUserIpAddr();

            html_pdf::create([
                'urlName' => $request->post('urlToPDF'),
                'hostName' => $hostName
            ]);

			$ilovepdfTask = new HtmlpdfTask('project_public_0ba8067b84cb4d4582b8eac3aa0591b2_XwmRS824bc5681a3ca4955a992dde44da6ac1','secret_key_937ea5acab5e22f54c6c7601fd7866dc_jT3DA5ed31082177f48cd792801dcf664c41b');
            $ilovepdfTask->setFileEncryption('XrPiOcvugxyGrJnX');
			$pdfFile = $ilovepdfTask->addUrl($request->post('urlToPDF'));
			$ilovepdfTask->setOutputFileName('captured');
			$ilovepdfTask->execute();
			$ilovepdfTask->download($pdfProcessed_Location);
			
			$download_pdf = 'temp/captured.pdf';
            
			if (file_exists(public_path('temp').'/captured.pdf')) {
				return redirect('htmltopdf')->with('success',$download_pdf);
			} else {
				return redirect('htmltopdf')->withError('error',' has failed to convert !')->withInput();
			}
        }
    }
}