<?php
 
namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\pdf_word;
use Aspose\Words\WordsApi;
use Aspose\Words\Model\Requests\{SaveAsRequest, UploadFileRequest};
use Aspose\Words\Model\{DocxSaveOptionsData};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Spatie\PdfToImage\Pdf;

class pdftowordController extends Controller
{
    public function word(){
		return view('pdftoword');
	}

    public function pdf_word(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
			'file' => 'mimes:pdf|max:25000',
            'fileAlt' => ''
		]);

        if($validator->fails()) {
            return redirect('pdftoword')->withErrors($validator->messages())->withInput();
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
								return redirect('pdftoword')->with('upload','thumbnail/'.$pdfNameWithoutExtension.'.png');
							} else {
								return redirect('pdftoword')->withError('error',' has failed to upload !')->withInput();
							}
						} else {
							return redirect('pdftoword')->withError('error',' has failed to upload !')->withInput();
						}
					} else {
						return redirect('pdftoword')->withError('error',' FILE NOT FOUND !')->withInput();
					}
				} else if ($request->post('formAction') == "convert") {
					if(isset($_POST['fileAlt'])) {
						$pdfUpload_Location = public_path('upload-pdf');
						$file = 'public/'.$request->post('fileAlt');
						$pdfProcessed_Location = public_path('temp');
						$pdfName = basename($file);
						$pdfNameWithoutExtension = basename($pdfName, ".pdf");
						$fileSize = filesize($file);
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");
                
                        pdf_word::create([
                            'fileName' => $pdfName,
                            'fileSize' => $newFileSize,
                            'hostName' => $hostName
                        ]);
                        
						ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                        $wordsApi = new WordsApi('73751f49-388b-4366-aeb4-d76587d5123e', '1792ea481716ff7788b276c8c88df6b8');
                        ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
						$uploadFileRequest = new UploadFileRequest($file, $pdfName);
                        ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
						$wordsApi->uploadFile($uploadFileRequest);
                        ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
						$requestSaveOptionsData = new DocxSaveOptionsData(array(
                            "save_format" => "docx",
                            "file_name" => 'EMSITPRO-PDFTools/Completed/'.$pdfNameWithoutExtension.".docx",
                        ));

                        $request = new SaveAsRequest(
                            $pdfName,
                            $requestSaveOptionsData,
                            NULL,
                            NULL,
                            NULL,
                            NULL
                        );
						ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now
                        $result = $wordsApi->saveAs($request);
						ini_set('memory_limit','-1'); // Server side exhausted memory limit, bypass it for now

                        if (json_decode($result, true) !== NULL) {
                            $download_word = 'https://drive.google.com/drive/folders/1D3YicPoJDk595tVw01NUyx_Osf3Q2Ca8?usp=sharing';
                            return redirect('pdftoword')->with('success',$download_word);
                        } else {
                            return redirect('pdftoword')->withError('error',' has failed to convert !')->withInput();
                        }
					} else {
						return redirect('pdftoword')->withError('error',' REQUEST NOT FOUND !')->withInput();
					}
				} else {
					return redirect('pdftoword')->withError('error',' FILE NOT FOUND !')->withInput();
				}
			} else {
				return redirect('pdftoword')->withError('error',' REQUEST NOT FOUND !')->withInput();
			}
        }
    }
}