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
            return redirect('pdftoexcel')->withErrors($validator->messages())->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = public_path('upload-pdf');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(public_path('thumbnail'));
							if (file_exists(public_path('thumbnail').'/1.png')) {
								$thumbnail = file(public_path('thumbnail').'/1.png');
								rename(public_path('thumbnail').'/1.png', public_path('thumbnail').'/'.$pdfNameWithoutExtension.'.png');
								return redirect('pdftoexcel')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('pdftoexcel')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('pdftoexcel')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('pdftoexcel')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "convert") {
					if(isset($_POST['fileAlt'])) {
						$pdfUpload_Location = public_path('upload-pdf');
						$file = 'public/'.$request->post('fileAlt');
						$pdfProcessed_Location = public_path('temp');
						$pdfName = basename($file);
						$pdfNameWithoutExtension = basename($file, ".pdf");
						$fileSize = filesize($file);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
			
						pdf_excel::create([
                            'fileName' => $pdfName,
                            'fileSize' => $newFileSize,
                            'hostName' => $hostName
                        ]);
            
                        $c = curl_init();

						$cfile = curl_file_create($pdfUpload_Location.'/'.$file->getClientOriginalName(), 'application/pdf');

						$apikey = 'dgxqu0tl0w06';
						curl_setopt($c, CURLOPT_URL, "https://pdftables.com/api?key=$apikey&format=xlsx-single");
						curl_setopt($c, CURLOPT_POSTFIELDS, array('file' => $cfile));
						curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($c, CURLOPT_FAILONERROR,true);
						curl_setopt($c, CURLOPT_ENCODING, "gzip,deflate");

						$result = curl_exec($c);

						if (curl_errno($c) > 0) {
							return redirect('pdftoexcel')->withError('error',' has failed to convert !')->withInput();
							curl_close($c);
						} else {
							file_put_contents ($pdfProcessed_Location.'/'.$pdfNameWithoutExtension.'.xls', $result);
							curl_close($c);
							if (file_exists($pdfProcessed_Location.$pdfNameWithoutExtension.'.xls')) {
								$download_excel = $pdfProcessed_Location.$pdfNameWithoutExtension.'.xls';
                                return redirect('pdftoexcel')->with('success','temp'.'/'.$pdfNameWithoutExtension.'.xls');
                            } else {
                                return redirect('pdftoexcel')->withError('error',' has failed to convert !')->withInput();
                            }
                        }
					} else {
						return redirect('pdftoexcel')->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect('pdftoexcel')->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect('pdftoexcel')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
        }
    }
}