<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_excel;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Spatie\PdfToImage\Pdf;

class pdftoexcelController extends Controller
{
    public function excel(){
		return view('pdftoexcel');
	}

    public function pdf_excel(Request $request): RedirectResponse{
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
				} else if ($request->post('formAction') == "convert") {
					if(isset($_POST['fileAlt'])) {
						$pdfUpload_Location = env('pdf_upload');
						$file = $request->post('fileAlt');
						$pdfProcessed_Location = 'temp';
						$pdfName = basename($request->post('fileAlt'));
						$pdfNameWithoutExtension = basename($request->post('fileAlt'), ".pdf");
						$fileSize = filesize($request->post('fileAlt'));
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			
						pdf_excel::create([
                            'fileName' => $pdfName,
                            'fileSize' => $newFileSize,
                            'hostName' => $hostName
                        ]);
            
                        $c = curl_init();

						$cfile = curl_file_create($pdfUpload_Location.'/'.$file->getClientOriginalName(), 'application/pdf');

						$apikey = env('PDFTABLES_API_KEY');
						curl_setopt($c, CURLOPT_URL, "https://pdftables.com/api?key=$apikey&format=xlsx-single");
						curl_setopt($c, CURLOPT_POSTFIELDS, array('file' => $cfile));
						curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($c, CURLOPT_FAILONERROR,true);
						curl_setopt($c, CURLOPT_ENCODING, "gzip,deflate");

						$result = curl_exec($c);

						if (curl_errno($c) > 0) {
							return redirect()->back()->withError('error',' has failed to convert !')->withInput();
							curl_close($c);
						} else {
							file_put_contents ($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xls', $result);
							curl_close($c);
							if (file_exists($pdfProcessed_Location.$pdfNameWithoutExtension.'.xls')) {
								$download_excel = $pdfProcessed_Location.$pdfNameWithoutExtension.'.xls';
                                return redirect()->back()->with('success',$download_excel);
                            } else {
                                return redirect()->back()->withError('error',' has failed to convert !')->withInput();
                            }
                        }
					} else {
						return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect()->back()->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect()->back()->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
        }
    }
}