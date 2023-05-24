<?php

namespace App\Http\Controllers;

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
		return redirect()->back()->withErrors($validator->messages())->withInput();
	} else {
		$pdfUpload_Location = 'upload-pdf';
		$pdfProcessed_Location = 'temp';
		$hostName = AppHelper::instance()->getUserIpAddr();

		html_pdf::create([
			'urlName' => $request->post('urlToPDF'),
			'hostName' => $hostName
		]);

		$ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
		$ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
		$pdfFile = $ilovepdfTask->addUrl($request->post('urlToPDF'));
		$ilovepdfTask->setOutputFileName('captured');
		$ilovepdfTask->execute();
		$ilovepdfTask->download($pdfProcessed_Location);

		$download_pdf = $pdfProcessed_Location.'/captured.pdf';

		if(is_file($pdfUpload_Location.'/'.$file->getClientOriginalName())) {
			unlink($pdfUpload_Location.'/'.$file->getClientOriginalName());
		}

		if (file_exists($download_pdf)) {
			return redirect()->back()->with('success',$download_pdf);
		} else {
			return redirect()->back()->withError('error',' has failed to convert !')->withInput();
		}
        }
    }
}
