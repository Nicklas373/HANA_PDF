<!DOCTYPE html>
@extends('layouts.default') @section('content')
<div class="px-4 md:px-12">
  <section>
    <div class="z-0 mt-24 max-w-screen-xl px-4 py-8">
      <h1 class="mb-4 mt-6 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 sm:mt-0 lg:text-6xl">PDF Merge</h1>
      <p class="mb-4 text-base font-poppins font-thin text-gray-500 lg:text-2xl">Combine several PDF into one merged PDF file</p>
    </div>
  </section>
  <form action="/api/v1/proc/merge" id="splitForm" method="POST" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="grid-columns-3 mx-auto mb-8 grid gap-4 p-4" id="grid-layout">
      <div class="grid gap-4 md:grid-cols-2 md:gap-20">
        <div>
          <label class="font-poppins mb-2 block text-base font-semibold text-slate-900" for="multiple_files">Upload PDF file</label>
          <input class="font-poppins block w-full cursor-pointer rounded-lg border border-gray-300 text-sm text-slate-900 shadow-inner" aria-describedby="multiple_files_help" id="multiple_files" name="file[]" type="file" accept="application/pdf" onclick="changeButtonColor('kaoB')" multiple />
          <pre id="filelist" class="font-poppins mb-4 mt-4 flex rounded-lg bg-white bg-opacity-50 p-4 text-sm text-gray-400" style="display:none; white-space: pre-line;">
                            <span id="err-list-title" class="font-poppins text-sky-500 font-bold">PDF List</span>
                            <ul id="pre-title"class="mt-1.5 list-disc list-inside"></ul>
                        </pre>
          <p class="font-poppins mt-1 text-sm text-gray-500" id="multiple_files_help">PDF (Max. 25 MB) | Multiple upload files supported.</p>
          @if ($message = Session::get('stats'))
          <div id="alert-additional-content-3" class="mb-2 mt-4 rounded-lg border border-green-300 bg-green-50 p-4 text-green-800" role="alert">
            <div class="flex items-center">
              <svg class="mr-2 h-4 w-4 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
              </svg>
              <span class="sr-only">Info</span>
              <h3 class="font-poppins text-sm">PDF has successfully merged !</h3>
              <br /><br />
            </div>
            <div class="flex">
              <button type="button" class="font-poppins mr-2 inline-flex items-center rounded-lg bg-green-800 px-3 py-1.5 text-center text-xs text-green-50 focus:outline-none focus:ring-4 focus:ring-green-100">
                <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
                  <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
                </svg>
                <b><a href="{{ session('res') }}">Download PDF</a></b>
              </button>
              <button type="button" class="font-poppins mr-2 inline-flex items-center rounded-lg border border-green-800 bg-green-50 px-3 py-1.5 text-center text-xs text-green-800 hover:bg-green-100" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                <b>Dismiss</b>
              </button>
            </div>
            <div class="mb-4 mt-2"></div>
          </div>
          @elseif($message = Session::get('error'))
          <div class="font-poppins mb-2 mt-4 flex rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <svg class="mr-3 mt-[2px] inline h-4 w-4 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
            </svg>
            <span class="sr-only">Danger</span>
            <div>
              <span class="text-sm"><b>PDF has failed to merged !</b></span>
              <br />
              <br />
              <span class="text-sm">Errors may occur that may come from this factor:</span>
              <ul class="ml-4 mt-1.5 list-inside list-disc text-xs">
                <li>Error due failure connection to API</li>
                <li>Error while uploading PDF to the server</li>
                <li>Filename contain ambigous characters or symbols</li>
              </ul>
              <br />
              @error('error')
              <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
              @enderror
              <br />
              @error('processId')
              <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
              @enderror
            </div>
          </div>
          @elseif ($errors->any()) @error('error')
          <div class="font-poppins mb-2 mt-4 flex rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
            <svg class="mr-3 mt-[2px] inline h-4 w-4 flex-shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
            </svg>
            <span class="sr-only">Danger</span>
            <div>
              <span class="text-sm"><b>PDF has failed to merged !</b></span>
              <br />
              <span class="text-sm">Errors may occur that may come from this factor:</span>
              <ul class="ml-4 mt-1.5 list-inside list-disc text-xs">
                <li>Error due failure connection to API</li>
                <li>Error while uploading PDF to the server</li>
                <li>Filename contain ambigous characters or symbols</li>
              </ul>
              <br />
              @error('error')
              <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
              @enderror
              <br />
              @error('processId')
              <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
              @enderror
            </div>
          </div>
          @enderror @endif
        </div>
        <div class="sm:w-5/5 grid h-16 grid-cols-2 gap-4 md:w-4/5 lg:w-3/5 xl:w-2/5">
          <button type="submit" id="submitBtn" name="formAction" class="font-poppins mx-auto mt-8 h-10 w-full cursor-pointer rounded-lg border border-sky-400 font-semibold text-sky-400 hover:bg-sky-400 hover:text-white" value="upload">Upload PDF</button>
          <button type="submit" id="submitBtn_1" name="formAction" class="font-poppins mx-auto mt-8 h-10 w-full cursor-pointer rounded-lg border bg-sky-400 font-semibold text-white" style="display: none;" value="merge">Merge PDF</button>
        </div>
      </div>
      <div class="mt-6">
        <div id="pdfPreview" class="mb-4 mt-4 grid grid-cols-1 gap-10 md:grid-cols-4 xl:mb-0" name="pdfPreview">
          @if($message = Session::get('status'))
          <?php
                                $pdfInArray = session('pdfImplodeArray');
                                $pdfNameInArray = session('pdfOrigName');
                                echo '<input type="text" id="fileAlt" name="fileAlt" class="" placeholder="" style="display: none;" value="'.$pdfInArray.'">'; $pdfToArr = explode(',', $pdfInArray); $pdfNameToArr = explode(',', $pdfNameInArray); $i = 1; foreach (array_combine($pdfToArr, $pdfNameToArr) as $pdfFileName => $pdfOrigName) { $pdfRealName = trim($pdfFileName,"//storage//temp-merge//"); echo '
          <div>
            <label for="fileAlt" class="font-poppins mb-2 block text-base font-semibold text-slate-900">PDF '.$i.'</label>
            <div id="caption" class="" placeholder="" style="display: none;" value="'.$pdfRealName.'"></div>
            <div id="adobe-dc-view'.$i.'" class="h-80 w-full"></div>
          </div>
          <script src="https://acrobatservices.adobe.com/view-sdk/viewer.js"></script>
          <script type="text/javascript">
            document.addEventListener("adobe_dc_view_sdk.ready", function(){
                var adobeDCView = new AdobeDC.View({clientId: "'.env('ADOBE_CLIENT_ID').'", divId: "adobe-dc-view'.$i.'"});
                adobeDCView.previewFile({
                    content:{location: {url: "'.$pdfFileName.'"}},
                    metaData:{fileName: "'.$pdfOrigName.'"}
                }, {embedMode: "SIZED_CONTAINER", focusOnRendering: true, showDownloadPDF: false});
            });
          </script>
          '; $i++; } ?> @endif
        </div>
      </div>
    </div>
  </form>
  @stop
</div>
