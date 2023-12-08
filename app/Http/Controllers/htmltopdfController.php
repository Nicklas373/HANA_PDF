<?php

namespace App\Http\Controllers;

use App\Helpers\AppHelper;
use App\Models\html_pdf;
use App\Models\init_pdf;
use Illuminate\Database\QueryException;
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
            try {
                DB::table('pdf_init')->insert([
                    'processId' => $uuid,
                    'err_reason' => $validator->messages(),
                    'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                ]);
                return redirect()->back()->withErrors(['error'=>'URL validation failed !', 'processId'=>$uuid])->withInput();
            } catch (QueryException $ex) {
                return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
            }
        } else {
            $str = rand(1000,10000000);
            $pdfEncKey = bin2hex(random_bytes(16));
            $pdfDefaultFileName ='pdf_convert_'.substr(md5(uniqid($str)), 0, 8);
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
                    try {
                        DB::table('pdf_html')->insert([
                            'processId' => $uuid,
                            'urlName' => $request->post('urlToPDF'),
                            'result' => false,
                            'err_reason' => 'URL not valid or not found !',
                            'err_api_reason' => '404',
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        return redirect()->back()->withErrors(['error'=>'URL not valid or not found !', 'processId'=>$uuid])->withInput();
                    } catch (QueryException $ex) {
                        return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                    } catch (\Exception $e) {
                        DB::table('pdf_html')->insert([
                            'processId' => $uuid,
                            'urlName' => 'null',
                            'result' => false,
                            'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                            'err_api_reason' => $e->getMessage(),
                            'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                        ]);
                        return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                    }
                }
            }
            try {
                $ilovepdfTask = new HtmlpdfTask(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));
                $ilovepdfTask->setEncryptKey($pdfEncKey);
                $ilovepdfTask->setEncryption(true);
                $pdfFile = $ilovepdfTask->addUrl($newUrl);
                $ilovepdfTask->setOutputFileName($pdfDefaultFileName);
                $ilovepdfTask->execute();
                $ilovepdfTask->download(Storage::disk('local')->path('public/'.$pdfProcessed_Location));
            } catch (\Ilovepdf\Exceptions\StartException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on StartException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\AuthException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on AuthException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\UploadException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on UploadException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\ProcessException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on ProcessException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\DownloadException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on DownloadException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\TaskException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on TaskException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Ilovepdf\Exceptions\PathException $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on PathException',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } catch (\Exception $e) {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'iLovePDF API Error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            }
            if (file_exists(Storage::disk('local')->path('public/'.$pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf'))) {
                $download_pdf = Storage::disk('local')->url($pdfProcessed_Location.'/'.$pdfDefaultFileName.'.pdf');
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => true,
                        'err_reason' => 'null',
                        'err_api_reason' => 'null',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->with(["stats" => "scs", "res"=>$download_pdf]);
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            } else {
                try {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => $newUrl,
                        'result' => false,
                        'err_reason' => 'Failed to download converted file from iLovePDF API !',
                        'err_api_reason' => 'null',
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                } catch (QueryException $ex) {
                    return redirect()->back()->withErrors(['error'=>'Database connection error !', 'processId'=>'null'])->withInput();
                } catch (\Exception $e) {
                    DB::table('pdf_html')->insert([
                        'processId' => $uuid,
                        'urlName' => 'null',
                        'result' => false,
                        'err_reason' => 'Eloquent transaction error !, Catch on Exception',
                        'err_api_reason' => $e->getMessage(),
                        'procStartAt' => AppHelper::instance()->getCurrentTimeZone()
                    ]);
                    return redirect()->back()->withErrors(['error'=>'HTML to PDF process failed !', 'processId'=>$uuid])->withInput();
                }
            }
        }
    }
}
