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
            return redirect()->back()->withErrors($validator->messages())->withInput();
        } else {
            if(isset($_POST['formAction']))
			{
				if($request->post('formAction') == "upload") {
					if($request->hasfile('file')) {
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->file('file');
						$file->move($pdfUpload_Location,$file->getClientOriginalName());
						$pdfFileName = $pdfUpload_Location.'/'.$file->getClientOriginalName();
						$pdfNameWithoutExtension = basename($file->getClientOriginalName(), '.pdf');

						if (file_exists($pdfFileName)) {
							$pdf = new Pdf($pdfFileName);
							$pdf->setPage(1)
								->setOutputFormat('png')
								->width(400)
								->saveImage(env('PDF_THUMBNAIL'));
							if (file_exists(env('PDF_THUMBNAIL').'/1.png')) {
								$thumbnail = file(env('PDF_THUMBNAIL').'/1.png');
								rename(env('PDF_THUMBNAIL').'/1.png', env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
								return redirect()->back()->with('upload','/'.env('PDF_THUMBNAIL').'/'.$pdfNameWithoutExtension.'.png');
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
						$pdfUpload_Location = env('PDF_UPLOAD');
						$file = $request->post('fileAlt');
						$pdfProcessed_Location = 'temp';
						$pdfName = basename($request->post('fileAlt'));
						$pdfNameWithoutExtension = basename($pdfName, ".pdf");
						$fileSize = filesize($request->post('fileAlt'));
						$hostName = AppHelper::instance()->getUserIpAddr();
						$newFileSize = AppHelper::instance()->convert($fileSize, "MB");

                        pdf_word::create([
                            'fileName' => $pdfName,
                            'fileSize' => $newFileSize,
                            'hostName' => $hostName
                        ]);

                        $wordsApi = new WordsApi(env('ASPOSE_CLOUD_CLIENT_ID'), env('ASPOSE_CLOUD_TOKEN'));
                        $uploadFileRequest = new UploadFileRequest($file, $pdfName);
                        $wordsApi->uploadFile($uploadFileRequest);
                        $requestSaveOptionsData = new DocxSaveOptionsData(array(
                            "save_format" => "docx",
                            "file_name" => env('ASPOSE_CLOUD_STORAGE_COMPLETED_DIR').$pdfNameWithoutExtension.".docx",
                        ));

                        $request = new SaveAsRequest(
                            $pdfName,
                            $requestSaveOptionsData,
                            NULL,
                            NULL,
                            NULL,
                            NULL
                        );
                        $result = $wordsApi->saveAs($request);

                        if (json_decode($result, true) !== NULL) {
                            $download_word = env('ASPOSE_CLOUD_STORAGE_COMPLETED_LINK');
                            return redirect()->back()->with('success',$download_word);
                        } else {
                            return redirect()->back()->withError('error',' has failed to convert !')->withInput();
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
