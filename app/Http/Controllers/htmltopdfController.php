<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\html_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Ilovepdf\Ilovepdf;
use Ilovepdf\HtmlpdfTask;
use Ilovepdf\Exceptions;

class htmltopdfController extends Controller
{
    public function html_pdf(Request $request): RedirectResponse{
        $validator = Validator::make($request->all(),[
		    'urlToPDF' => 'required',
	    ]);

        $uuid = AppHelper::Instance()->get_guid();

        if($validator->fails()) {
            return redirect()->back()->withErrors(['error'=>$validator->messages(), 'processId'=>$uuid])->withInput();
        } else {
            $pdfProcessed_Location = env('PDF_DOWNLOAD');
            $pdfUpload_Location = env('PDF_UPLOAD');
            $pdfUrl = $request->post('urlToPDF');
            $newUrl = '';
            if (AppHelper::Instance()->checkWebAvailable($pdfUrl)) {
                $newUrl = $pdfUrl;
            } else {
                if (AppHelper::Instance()->checkWebAvailable('https://'.$pdfUrl)) {
                    $newUrl = 'https://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('http://'.$pdfUrl)) {
                    $newUrl = 'http://'.$pdfUrl;
                } else if (AppHelper::Instance()->checkWebAvailable('www.'.$pdfUrl)) {
                    $newUrl = 'www.'.$pdfUrl;
                } else {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $request->post('urlToPDF'),
                        'result' => false,
                        'err_reason' => 'URL not valid or not found !',
                        'err_api_reason' => '404',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'URL not valid or not found !', 'processId'=>$uuid])->withInput();
                }
            }
            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                $pdfFile = $ilovepdfTask->addUrl($newUrl);
                $ilovepdfTask->setOutputFileName('captured');
                $ilovepdfTask->execute();
                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
            } catch (\Ilovepdf\Exceptions\StartException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\AuthException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\UploadException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\TaskException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\PathException $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            } catch (\Exception $e) {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                    'err_api_reason' => $e->getMessage(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !', 'processId'=>$uuid])->withInput();
            }
            if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/captured.pdf'))) {
                $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/captured.pdf');
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => true,
                    'err_reason' => null,
                    'err_api_reason' => null,
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
            } else {
                DB::table('pdf_html')->insert([
                    'processId' => $uuid,
                    'urlName' => $newUrl,
                    'result' => false,
                    'err_reason' => 'Failed to download converted file from iLovePDF API !',
                    'err_api_reason' => null,
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'HTML To PDF process failed !'])->withInput();
            }
        }
    }
}
