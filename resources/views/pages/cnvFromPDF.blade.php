<!DOCTYPE html>
@extends('layouts.default')
@section('content')
    <div class="px-4 md:px-12" id="cnvFrPDF">
        <section>
            <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
                <h1 class="mb-4 mt-6 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 sm:mt-0 lg:text-6xl">Convert from PDF</h1>
                <p class="mb-4 text-base font-poppins font-thin text-gray-500 lg:text-2xl">Convert PDF files into specified document format</p>
            </div>
        </section>
        <form action="/convert/pdf" id="splitForm" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="grid grid-columns-3 gap-4 p-4 mx-auto mb-8" id="grid-layout">
                <div class="grid md:grid-cols-2 gap-4 md:gap-20">
                    <div>
                        <label for="file_input" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Upload PDF file</label>
                        <input class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg shadow-inner cursor-pointer" aria-describedby="file_input_help" id="file_input" name="file" type="file" accept="application/pdf" onclick="changeButtonColor('kaoA')">
                        <p class="mt-1 font-poppins text-sm text-gray-500" id="file_input_help">PDF (Max. 25 MB)</p>
                        @if ($message = Session::get('stats'))
                        <div id="alert-additional-content-3" class="p-4 mt-4 mb-2 text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Info</span>
                                <h3 class="text-sm font-poppins">PDF has successfully converted !</h3>
                                <br><br>
                            </div>
                            <div class="flex">
                                <button type="button" class="text-green-50 bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-100 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center">
                                    <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"> <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path> <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path> </svg>
                                    <b><a href="{{ session('res') }}">Download Document</a></b>
                                </button>
                                <button type="button" class="text-green-800 bg-green-50 hover:bg-green-100 border border-green-800 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                                    <b>Dismiss</b>
                                </button>
                            </div>
                            <div class="mt-2 mb-4"></div>
                        </div>
                        @elseif($message = Session::get('error'))
                            <div class="flex p-4 mt-4 mb-2 text-sm font-poppins text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Danger</span>
                                <div>
                                    <span class="text-sm"><b>PDF has failed to compress !</b></span>
                                    <br>
                                    <br>
                                    <span class="text-sm">Errors may occur that may come from this factor:</span>
                                    <ul class="mt-1.5 ml-4 text-xs list-disc list-inside">
                                        <li>Error due failure connection to API </li>
                                        <li>Error while uploading PDF to the server</li>
                                        <li>Filename contain ambigous characters or symbols</li>
                                    </ul>
                                    <br>
                                    @error('error')
                                        <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
                                    @enderror
                                    <br>
                                    @error('processId')
                                        <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
                                    @enderror
                                </div>
                            </div>
                        @elseif ($errors->any())
                            @error('error')
                            <div class="flex p-4 mt-4 mb-2 text-sm font-poppins text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Danger</span>
                                <div>
                                    <span class="text-sm"><b>PDF has failed to compress !</b></span>
                                    <br>
                                    <span class="text-sm">Errors may occur that may come from this factor:</span>
                                    <ul class="mt-1.5 ml-4 text-xs list-disc list-inside">
                                        <li>Error due failure connection to API </li>
                                        <li>Error while uploading PDF to the server</li>
                                        <li>Filename contain ambigous characters or symbols</li>
                                    </ul>
                                    <br>
                                    @error('error')
                                        <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
                                    @enderror
                                    <br>
                                    @error('processId')
                                        <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
                                    @enderror
                                    </div>
                            </div>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-8 font-poppins font-semibold text-sky-400 border border-sky-400 rounded-lg cursor-pointer w-full h-10 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 hover:bg-sky-400 hover:text-white" value="upload">Upload PDF</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-20 mt-6">
                    <div id="pdfPreview" class="mt-4 mb-4 xl:mb-0" name="pdfPreview">
                        @if($message = Session::get('status'))
                            <?php
                                $pdfFileName = basename(session('pdfOriName'), '.png');
                                $pdfFileAppend = session('pdfRndmName');
                                $pdfThumbAppend = session('pdfThumbName');
                                $pdfRealName = session('pdfOriName');
                                $pdfClientID = env('ADOBE_CLIENT_ID');
                                echo '
                                <label for="fileAlt" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Preview</label>
                                <input type="text" id="fileAlt" name="fileAlt" class="" placeholder="" style="display: none;" value="'.$pdfFileAppend.'">
                                <div id="caption" class="" placeholder="" style="display: none;" value="'.$pdfRealName.'" ></div>
                                <div id="adobe-dc-view" class="w-full h-96"></div>
                                <script src="https://acrobatservices.adobe.com/view-sdk/viewer.js"></script>
                                <script type="text/javascript">
                                    document.addEventListener("adobe_dc_view_sdk.ready", function(){
                                        var adobeDCView = new AdobeDC.View({clientId: "'.$pdfClientID.'", divId: "adobe-dc-view"});
                                        adobeDCView.previewFile({
                                            content:{location: {url: "'.$pdfFileAppend.'"}},
                                            metaData:{fileName: "'.$pdfRealName.'"}
                                        }, {embedMode: "SIZED_CONTAINER", focusOnRendering: true, showDownloadPDF: false});
                                    });
                                </script>
                                ';
                            ?>
                        @endif
                    </div>
                    <div id="pdfCompLayout" class="mt-4" style="display: none;">
                        <label for="firstRadio" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Document Format</label>
                        <ul class="grid grid-cols-1 xl:grid-cols-4 gap-2 xl:gap-4 mt-4 mb-4">
                            <li id="firstCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400" onclick="checkValidation('cnvFrPDF')">
                                <input type="text" id="firstInput" class="" style="display: none;" value="cnvFrPDF">
                                <div class="flex" id="firstChk">
                                    <div class="flex items-center h-5">
                                        <input id="firstRadio" name="convertType" value="jpg" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="firstRadio" class="font-semibold text-sm text-slate-800 font-poppins" id="firstRadioText">Image</label>
                                        <p id="helper-firstRadioText" class="text-xs mt-1 font-normal font-poppins text-gray-500">(*.jpg)</p>
                                    </div>
                                </div>
                            </li>
                            <li id="secondCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400" onclick="checkValidation('cnvFrPDF')">
                                <input type="text" id="secondInput" class="" style="display: none;" value="cnvFrPDF">
                                <div class="flex" id="secondChk">
                                    <div class="flex items-center h-5">
                                        <input id="secondRadio" name="convertType" value="pptx" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="secondRadio" class="font-semibold text-sm text-slate-800 font-poppins" id="secondRadioText">Powerpoint Presentation</label>
                                        <p id="helper-secondRadioText" class="text-xs mt-1 font-normal font-poppins text-gray-500">(*.pptx)</p>
                                    </div>
                                </div>
                            </li>
                            <li id="thirdCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400" onclick="checkValidation('cnvFrPDF')">
                                <input type="text" id="thirdInput" class="" style="display: none;" value="cnvFrPDF">
                                <div class="flex" id="thirdChk" value="cnvFrPDF">
                                    <div class="flex items-center h-5">
                                        <input id="thirdRadio" name="convertType" value="excel" aria-describedby="helper-thirdRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="thirdRadio" class="font-semibold text-sm text-slate-800 font-poppins" id="thirdRadioText">Spreadsheet</label>
                                        <p id="helper-thirdRadioText" class="text-xs mt-1 font-normal font-poppins text-gray-500">(*.xlsx)</p>
                                    </div>
                                </div>
                            </li>
                            <li id="fourthCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400" onclick="checkValidation('cnvFrPDF')">
                                <input type="text" id="fourthInput" class="" style="display: none;" value="cnvFrPDF">
                                <div class="flex" id="fourthChk" value="cnvFrPDF">
                                    <div class="flex items-center h-5">
                                        <input id="fourthRadio" name="convertType" value="docx" aria-describedby="helper-fourthRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="fourthRadio" class="font-semibold text-sm text-slate-800 font-poppins" id="fourthRadioText">Word Document</label>
                                        <p id="helper-fourthRadioText" class="text-xs mt-1 font-normal font-poppins text-gray-500">(*.docx)</p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div dir="ltl">
                            <button type="submit" id="submitBtn_1" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-poppins font-semibold text-sky-400 border border-sky-400 rounded-lg cursor-pointer w-full h-10 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 hover:bg-sky-400 hover:text-white" value="convert" style="">Convert PDF</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
       @stop
    </div>
