<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\html_pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
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
            return redirect()->back()->withErrors(['error'=>$validator->messages(), 'uuid'=>$uuid])->withInput();
        } else {
            $pdfProcessed_Location = env('PDF_DOWNLOAD');
            $pdfUpload_Location = env('PDF_UPLOAD');
            $hostName = AppHelper::instance()->getUserIpAddr();

            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setFileEncryption(env('ILOVEPDF_ENC_KEY'));
                $pdfFile = $ilovepdfTask->addUrl($request->post('urlToPDF'));
                $ilovepdfTask->setOutputFileName('captured');
                $ilovepdfTask->execute();
                $ilovepdfTask->download($pdfProcessed_Location);
            }						catch (\Ilovepdf\Exceptions\StartException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\AuthException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\UploadException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\TaskException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Ilovepdf\Exceptions\PathException $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            } catch (\Exception $e) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                    'err_api_reason' => $e->getMessage(),
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'iLovePDF API Error !', 'uuid'=>$uuid])->withInput();
            }

            $download_pdf = $pdfProcessed_Location.'/captured.pdf';

            if (file_exists($download_pdf)) {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => true,
                    'err_reason' => null,
                    'err_api_reason' => null,
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
            } else {
                DB::table('html_pdfs')->insert([
                    'urlName' => $request->post('urlToPDF'),
                    'hostName' => $hostName,
                    'result' => false,
                    'err_reason' => 'Failed to download converted file from iLovePDF API !',
                    'err_api_reason' => null,
                    'uuid' => $uuid,
                    'created_at' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'Failed to download converted file from iLovePDF API !'])->withInput();
            }
        }
    }
}
