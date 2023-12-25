<!DOCTYPE html>
@extends('layouts.default')
@section('content')
    <div class ="px-4 md:px-12">
        <section>
            <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
                <h1 class="mb-4 mt-6 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 sm:mt-0 lg:text-6xl">PDF Watermark</h1>
                <p class="mb-4 text-base font-poppins font-thin text-gray-500 lg:text-2xl">Insert an image or text over PDF to selected pages or all page</p>
            </div>
        </section>
        <form action="/watermark/pdf" method="POST" enctype="multipart/form-data">
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
                                <h3 class="text-sm font-poppins">PDF has successfully applied watermark !</h3>
                                <br><br>
                            </div>
                            <div class="flex">
                                <button type="button" class="text-green-50 bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-100 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center">
                                    <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"> <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path> <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path> </svg>
                                    <b><a href="{{ session('res') }}">Download PDF</a></b>
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
                                    <span class="text-sm"><b>PDF has failed to apply watermark !</b></span>
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
                                    <span class="text-sm"><b>PDF has failed to apply watermark !</b></span>
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
                    <div class="grid grid-cols-2 gap-4 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 h-16">
                        <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-8 font-poppins font-semibold text-sky-400 border border-sky-400 rounded-lg cursor-pointer w-full h-10 hover:bg-sky-400 hover:text-white" value="upload">Upload PDF</button>
                        <button type="submit" id="submitBtn_1" name="formAction" class="mx-auto mt-8 border font-poppins font-semibold bg-sky-400 text-white rounded-lg cursor-pointer w-full h-10 hover:bg-transparent hover:text-sky-400 hover:border-sky-400" style="display: none;" value="watermark">Watermark PDF</button>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-20 mt-6">
                    <div id="pdfPreview" class="mt-4 mb-4 xl:mb-0" name="pdfPreview">
                        @if($message = Session::get('status'))
                            <?php
                                $pdfFileName = basename(session('pdfOriName'), '.avif');
                                $pdfFileAppend = session('pdfRndmName');
                                $pdfRealName = session('pdfOriName');
                                echo '
                                <label for="fileAlt" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Preview</label>
                                <input type="text" id="fileAlt" name="fileAlt" class="" placeholder="" style="display: none;" value="'.$pdfFileAppend.'">
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
                    <div id="pdfCompLayout" class="mt-4" style="display: none;">
                        <label for="firstRadio" class="block mb-2 font-poppins text-base font-semibold text-slate-900">Watermark Options</label>
                        <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4">
                            <li id="firstCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="firstInput" class="" style="display: none;" value="watermark">
                                <div class="flex" id="firstChk">
                                    <div class="flex items-center h-5">
                                        <input id="firstRadio" name="WatermarkOpt" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="firstRadio" class="font-semibold text-sm text-gray-500 font-poppins" id="firstRadioText">Image</label>
                                    </div>
                                </div>
                            </li>
                            <li id="secondCol" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="secondInput" class="" style="display: none;" value="watermark">
                                <div class="flex" id="secondChk">
                                    <div class="flex items-center h-5">
                                        <input id="secondRadio" name="WatermarkOpt" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="secondRadio" class="font-semibold text-sm text-gray-500 font-poppins" id="secondRadioText">Text</label>
                                    </div>
                                </div>
                            </li>
                        </ul>
                        <div class="mb-4 mt-6" id="wmMainLayout">
                            <div id="wmLayoutImage" style="display: none;">
                                <input id="wmTypeImage" type="radio" name="wmType" value="image" class="" style="display:none;" />
                                <div class="mb-8 mt-4">
                                  <label for="wm_file_input" class="font-poppins mb-4 block text-base font-semibold text-slate-900" for="wm_file_input">Image</label>
                                  <input class="font-poppins block w-5/6 cursor-pointer rounded-lg border border-gray-300 text-sm text-slate-900 shadow-inner" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/jpg,image/jpeg,image/avif" onFocusIn="checkValidation('wm_file_input')" onFocusOut="checkValidation('wm_file_input')" />
                                  <p class="font-poppins mt-1 text-sm text-gray-500" id="file_input_help">Image (Max. 5 MB)</p>
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioImageLayoutStyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Layer</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                                    <li id="wmColImageLayoutStyleA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmColImageLayoutStyleInputA" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageLayoutStyleA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageLayoutStyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-wmRadioImageLayoutStyleTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageLayoutStyleA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageLayoutStyleTextA">Above content</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColImageLayoutStyleB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColImageLayoutStyleInputB" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageLayoutStyleB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageLayoutStyleB" name="watermarkLayoutStyle" value="below" aria-describedby="helper-wmRadioImageLayoutStyleTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageLayoutStyleB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageLayoutStyleTextB">Below content</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="watermarkPageImage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Page</label>
                                  <input type="text" id="watermarkPageImage" name="watermarkPageImage" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onfocusout="checkValidation('watermarkPage')" />
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioImageRotationA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Orientation</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-4 xl:gap-4">
                                    <li id="wmColImageRotationA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColImageRotationInputA" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageRotationA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageRotationA" name="watermarkRotation" value="0" aria-describedby="helper-wmRadioImageRotationTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageRotationA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageRotationTextA">0°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColImageRotationB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColImageRotationInputB" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageRotationB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageRotationB" name="watermarkRotation" value="90" aria-describedby="helper-wmRadioImageRotationTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageRotationB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageRotationTextB">90°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColImageRotationC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColImageRotationInputC" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageRotationC">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageRotationC" name="watermarkRotation" value="180" aria-describedby="helper-wmRadioImageRotationTextC" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageRotationC" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageRotationTextC">180°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColImageRotationD" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColImageRotationInputD" class="" style="display: none;" value="wmImage">
                                      <div class="flex" id="wmChkImageRotationD">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioImageRotationD" name="watermarkRotation" value="270" aria-describedby="helper-wmRadioImageRotationTextD" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioImageRotationD" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioImageRotationTextD">270°</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8 grid grid-cols-1 gap-2">
                                  <div>
                                    <label id="Transparency" class="font-poppins mb-2 block text-base font-semibold text-slate-900" for="watermarkFontTransparency">Opacity</label>
                                    <div class="grid w-full grid-cols-2 gap-4">
                                      <input id="watermarkFontTransparency" name="watermarkFontImageTransparency" type="range" min="0" max="100" value="100" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer" oninput="showVal(this.value,'image')" onchange="showVal(this.value,'image')">
                                      <label id="TransparencyValueImage" class="font-poppins mt-2.5 block text-sm font-semibold text-gray-500" for="watermarkFontTransparency"></label>
                                    </div>
                                  </div>
                                </div>
                                <div id="isMosaicImageArea" class="mt-6">
                                  <div class="flex">
                                    <div class="flex h-5 items-center">
                                      <input id="isMosaicImage" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
                                    </div>
                                    <div class="ml-2 text-sm">
                                      <label for="isMosaicImage" class="font-poppins text-sm font-semibold text-slate-800">Mosaic Effects</label>
                                      <p id="isMosaicImageSub" class="font-poppins mt-1 text-xs font-normal text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
                                    </div>
                                  </div>
                                </div>
                            </div>
                            <div id="wmLayoutText" style="display: none;">
                                <input id="wmTypeText" type="radio" name="wmType" value="text" class="" style="display:none;" />
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioFontFamilyA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Font Family</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3">
                                    <li id="wmColFontFamilyA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmColFontFamilyInputA" class="" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyA" name="watermarkFontFamily" value="Arial" aria-describedby="helper-wmRadioFontFamilyTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextA">Arial</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontFamilyB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColFontFamilyInputB" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkFontFamilyB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyB" name="watermarkFontFamily" value="Arial Unicode MS" aria-describedby="helper-wmRadioFontFamilyTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextB">Arial Unicode MS</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontFamilyC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                       <input type="text" id="wmColFontFamilyInputC" class="" style="display: none;" value="wmText">
                                       <div class="flex" id="wmChkFontFamilyC">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyC" name="watermarkFontFamily" value="Comic Sans MS" aria-describedby="helper-wmRadioFontFamilyTextC" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyC" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextC">Comic Sans MS</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontFamilyD" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                       <input type="text" id="wmColFontFamilyInputD" class="" style="display: none;" value="wmText">
                                       <div class="flex" id="wmChkFontFamilyD">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyD" name="watermarkFontFamily" value="Courier" aria-describedby="helper-wmRadioFontFamilyTextD" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyD" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextD">Courier</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontFamilyE" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColFontFamilyInputE" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkFontFamilyE">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyE" name="watermarkFontFamily" value="Times New Roman" aria-describedby="helper-wmRadioFontFamilyTextE" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyE" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextE">Times New Roman</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontFamilyF" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                       <input type="text" id="wmColFontFamilyInputF" class="" style="display: none;" value="wmText">
                                       <div class="flex" id="wmChkFontFamilyF">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontFamilyF" name="watermarkFontFamily" value="Verdana" aria-describedby="helper-wmRadioFontFamilyTextF" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontFamilyF" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontFamilyTextF">Verdana</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8">
                                  <label for="watermarkText" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Text</label>
                                  <input type="text" id="watermarkText" name="watermarkText" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: Lorem ipsum dolor sit amet" onfocusout="checkValidation('watermarkText')" />
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="watermarkPageText" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Page</label>
                                  <input type="text" id="watermarkPageText" name="watermarkPageText" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onfocusout="checkValidation('watermarkPage')" />
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioFontStyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Font Style</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                                    <li id="wmColFontStyleA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColFontStyleInputA" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkFontStyleA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontStyleA" name="watermarkFontStyle" value="Regular" aria-describedby="helper-wmRadioFontStyleTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontStyleA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontStyleTextA">Regular</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontStyleB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                       <input type="text" id="wmColFontStyleInputB" class="" style="display: none;" value="wmText">
                                       <div class="flex" id="wmChkFontStyleB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontStyleB" name="watermarkFontStyle" value="Bold" aria-describedby="helper-wmRadioFontStyleTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontStyleB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontStyleTextB">Bold</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColFontStyleC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmColFontStyleInputC" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkFontStyleC">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioFontStyleC" name="watermarkFontStyle" value="Italic" aria-describedby="helper-wmRadioFontStyleTextC" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioFontStyleC" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioFontStyleTextC">Italic</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioLayoutStyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Layer</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                                    <li id="wmColLayoutStyleA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioLayoutStyleInputA" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkLayoutStyleA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioLayoutStyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-wmRadioLayoutStyleTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioLayoutStyleA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioLayoutStyleTextA">Above content</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColLayoutStyleB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioLayoutSytleInputB" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkLayoutStyleB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioLayoutStyleB" name="watermarkLayoutStyle" value="below" aria-describedby="helper-wmRadioLayoutStyleTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioLayoutStyleB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioLayoutStyleTextB">Below content</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8 mt-4">
                                  <label for="wmRadioRotationA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Orientation</label>
                                  <ul class="grid grid-cols-1 gap-2 xl:grid-cols-4 xl:gap-4">
                                    <li id="wmColRotationA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioRotationInputA" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkRotationA">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioRotationA" name="watermarkRotation" value="0" aria-describedby="helper-wmRadioRotationTextA" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioRotationA" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioRotationTextA">0°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColRotationB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioRotationInputB" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkRotationB">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioRotationB" name="watermarkRotation" value="90" aria-describedby="helper-wmRadioRotationTextB" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioRotationB" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioRotationTextB">90°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColRotationC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioRotationInputC" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkRotationC">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioRotationC" name="watermarkRotation" value="180" aria-describedby="helper-wmRadioRotationTextC" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioRotationC" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioRotationTextC">180°</label>
                                        </div>
                                      </div>
                                    </li>
                                    <li id="wmColRotationD" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                      <input type="text" id="wmRadioRotationInputD" class="" style="display: none;" value="wmText">
                                      <div class="flex" id="wmChkRotationD">
                                        <div class="flex h-5 items-center">
                                          <input id="wmRadioRotationD" name="watermarkRotation" value="270" aria-describedby="helper-wmRadioRotationTextD" type="radio" class="w-4 h-4 text-sky-400 border-slate-300 ring-sky-400 ring-0 hover:ring-2 hover:ring-sky-400 focus:ring-0" />
                                        </div>
                                        <div class="ml-4">
                                          <label for="wmRadioRotationD" class="font-poppins text-sm font-semibold text-gray-500" id="wmRadioRotationTextD">270°</label>
                                        </div>
                                      </div>
                                    </li>
                                  </ul>
                                </div>
                                <div class="mb-8 grid grid-cols-1 gap-2">
                                  <div>
                                    <label id="Transparency" class="font-poppins mb-2 block text-base font-semibold text-slate-900" for="watermarkFontTransparency">Opacity</label>
                                    <div class="grid w-full grid-cols-2 gap-x-4">
                                      <input id="watermarkFontTransparency" name="watermarkFontTextTransparency" type="range" min="0" max="100" value="100" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer" oninput="showVal(this.value,'text')" onchange="showVal(this.value,'text')">
                                      <label id="TransparencyValueText" class="font-poppins mt-2.5 block text-sm font-semibold text-gray-500" for="watermarkFontTransparency"></label>
                                    </div>
                                  </div>
                                </div>
                                <div id="isMosaicTextArea" class="mt-6">
                                  <div class="flex">
                                    <div class="flex h-5 items-center">
                                      <input id="isMosaicText" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
                                    </div>
                                    <div class="ml-2 text-sm">
                                      <label for="isMosaicText" class="font-poppins text-sm font-semibold text-slate-800">Mosaic Effects</label>
                                      <p id="isMosaicTextSuv" class="font-poppins mt-1 text-xs font-normal text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
                                    </div>
                                  </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
       @stop
    </div>
