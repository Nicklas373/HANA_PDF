<!DOCTYPE html>
@extends('layouts.default')
@section('content')
    <div class="px-4 md:px-12">
        <section>
            <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
                <h1 class="mb-4 mt-6 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 sm:mt-0 lg:text-6xl">PDF Split</h1>
                <p class="mb-4 text-base font-poppins font-thin text-gray-500 lg:text-2xl">Separate one page or a whole page into independent PDF files</p>
            </div>
        </section>
        <form action="/api/v1/proc/split" id="splitForm" method="POST" enctype="multipart/form-data">
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
                                <h3 class="text-sm font-poppins">{{ session('titleMessage')}}</h3>
                                <br><br>
                            </div>
                            <div class="flex">
                                <button type="button" class="text-green-50 bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-100 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center">
                                    <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"> <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path> <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path> </svg>
                                    <b><a href="{{ session('res') }}">Download PDF</a></b>
                                </button>
                                <button type="button" class="text-green-800 bg-green-50 border border-green-800 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
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
                                    @error('titleMessage')
                                        <span class="text-sm"><b>{{ $message }}</b></span>
                                    @enderror
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
                                    @error('titleMessage')
                                        <span class="text-sm"><b>{{ $message }}</b></span>
                                    @enderror
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
                                $pdfFileName = basename(session('pdfOriName'), '.avif');
                                $pdfFileAppend = session('pdfRndmName');
                                $pdfRealName = session('pdfOriName');
                                $pdfPages = session('pdfTotalPages');
                                echo '
                                <label for="fileAlt" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Preview</label>
                                <input type="text" id="fileAlt" name="fileAlt" class="" placeholder="" style="display: none;" value="'.$pdfFileAppend.'">
                                <input type="text" id="totalPage" name="totalPage" class="" placeholder="" style="display: none;" value="'.$pdfPages.'">
                                <div id="caption" class="" placeholder="" style="display: none;" value="'.$pdfRealName.'" ></div>
                                <div id="adobe-dc-view" class="w-full h-80"></div>
                                <script src="https://acrobatservices.adobe.com/view-sdk/viewer.js"></script>
                                <script type="text/javascript">
                                    document.addEventListener("adobe_dc_view_sdk.ready", function(){
                                        var adobeDCView = new AdobeDC.View({clientId: "'.env('ADOBE_CLIENT_ID').'", divId: "adobe-dc-view"});
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
                    <div id="pdfCompLayout" class="mt-4">
                        <div id="splitLayout1" style="display: none;">
                            <label for="firstRadio" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Split or Delete Page</label>
                            <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4 mt-4 mb-4">
                                <li id="firstCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="firstInput" class="" style="display: none;" value="split">
                                    <div class="flex" id="firstChk">
                                        <div class="flex items-center h-5">
                                            <input id="firstRadio" value="split" name="SplitOpt" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                        </div>
                                        <div class="ml-4">
                                            <label for="firstRadio" class="font-semibold text-sm text-gray-500 font-poppins" id="firstRadioText">Split Page</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="secondCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="secondInput" class="" style="display: none;" value="split">
                                    <div class="flex" id="secondChk">
                                        <div class="flex items-center h-5">
                                            <input id="secondRadio" value="delete" name="SplitOpt" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                        </div>
                                        <div class="ml-4">
                                            <label for="secondRadio" class="font-semibold text-sm text-gray-500 font-poppins" id="secondRadioText">Delete Page</label>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                            <div class="mb-4 mt-6" id="splitLayout2_split" style="display: none;">
                                <label for="thirdRadio" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Page Options</label>
                                <ul id="splitRadio" class="mb-4 mt-4 grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                                    <li id="thirdCol" class="mt-2 rounded border border-slate-200 p-2 hover:border-sky-400">
                                        <input type="text" id="thirdInput" class="" style="display: none;" value="split">
                                        <div class="flex" id="thirdChk">
                                            <div class="flex h-5 items-center">
                                                <input id="thirdRadio" value="selPages" name="SplitOpt2" aria-describedby="helper-thirdRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" onclick="splitLayout3_wthn()" />
                                            </div>
                                            <div class="ml-4">
                                                <label for="thirdRadio" class="font-poppins text-sm font-semibold text-gray-500" id="thirdRadioText">Range</label>
                                            </div>
                                        </div>
                                    </li>
                                    <li id="fourthCol" class="mt-2 rounded border border-slate-200 p-2 hover:border-sky-400">
                                        <input type="text" id="fourthInput" class="" style="display: none;" value="split">
                                        <div class="flex" id="fourthChk">
                                            <div class="flex h-5 items-center">
                                                <input id="fourthRadio" value="cusPages" name="SplitOpt2" aria-describedby="helper-fourthRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" onclick="splitLayout3_cstm()" />
                                            </div>
                                            <div class="ml-4">
                                                <label for="fourthRadio" class="font-poppins text-sm font-semibold text-gray-500" id="fourthRadioText">Custom</label>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="mb-4 mt-6" id="splitLayout2_delete" style="display: none;">
                                <div class="grid gap-2 md:grid-cols-1">
                                    <div>
                                        <label for="customPageDelete" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Custom Page</label>
                                        <input type="text" id="customPageDelete" name="customPageDelete" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusOut="checkValidation('extCustomPage')" />
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4 mt-6" id="splitLayout3Cstm" style="display: none;">
                                <div class="grid gap-2 md:grid-cols-1">
                                    <div>
                                        <label for="customPageSplit" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Custom Page</label>
                                        <input type="text" id="customPageSplit" name="customPageSplit" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusOut="checkValidation('splitCustomPage')" />
                                    </div>
                                    <div class="mt-2 flex items-center">
                                        <input id="mergePDFSplit" name="mergePDF" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
                                        <label for="mergePDFSplit" class="font-poppins ml-2 text-xs text-gray-900">Merge all Page into one PDF file.</label>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-4 mt-6" id="splitLayout3Wthn" style="display: none;">
                                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3 xl:gap-8">
                                    <div>
                                        <label for="fromPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">First Page</label>
                                        <input type="number" id="fromPage" name="fromPage" class="font-poppins mt-4 block w-fit rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="3" placeholder="Example: 1" onFocusOut="checkValidation('splitFirstPage')" />
                                    </div>
                                    <div>
                                        <label for="toPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Last Page</label>
                                        <input type="number" id="toPage" name="toPage" class="font-poppins mt-4 block w-fit rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="3" placeholder="Example: 10" onFocusOut="checkValidation('splitLastPage')" />
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center">
                                    <input id="mergePDF" name="mergePDF" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
                                    <label for="mergePDF" class="font-poppins ml-2 text-xs text-gray-900">Merge all Page into one PDF file.</label>
                                </div>
                            </div>
                            <div dir="ltr">
                                <button type="submit" id="submitBtn_2" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-poppins font-semibold text-white bg-sky-400 border border-sky-400 rounded-lg cursor-pointer w-full h-10 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 hover:bg-sky-400 hover:text-white" value="split" style="display: none;">Split PDF</button>
                                <button type="submit" id="submitBtn_3" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-poppins font-semibold text-white bg-sky-400 border border-sky-400 rounded-lg cursor-pointer w-full h-10 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 hover:bg-sky-400 hover:text-white" value="delete" style="display: none;">Delete Page</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
       @stop
    </div>
