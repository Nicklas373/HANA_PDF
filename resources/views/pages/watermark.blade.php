<!DOCTYPE html>
@extends('layouts.alternate-layout')
@section('content')
    <div class ="px-4 md:px-12" id="watermark">
        <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg lg:max-w-6xl">
            <div class="text-start mx-6">
                <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Watermark</div>
                <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Add and customize watermark with image or text over your PDF.</div>
            </div>
        </section>
        <div class="flex flex-col p-2" id="dropzoneWatermark">
            <form action="{{ url('api/v1/file/upload') }}" method="post" class="dropzone flex flex-col lg:flex-row xl:flex-row mx-4 items-center justify-center w-6/6 lg:w-4/6 min-h-96 h-fit lg:h-72 max-h-full lg:overflow-y-auto cursor-pointer bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 mb-2" id="dropzoneAreaSingle">
                {{ csrf_field() }}
                <div class="flex flex-col items-center justify-content p-4" id="dropzoneUiInit">
                    <img class="p-4 h-24 w-24" src="{{ asset('assets/icons/placeholder_pdf.svg') }}">
                    <p class="mb-2 text-md text-lt3 font-quicksand font-medium">Drop PDF files here</p>
                    <p class="text-xs text-lt3 font-quicksand">Or</p>
                    <button type="button" id="dropzoneUploadInit" class="mx-auto mt-2 p-4 text-xs font-quicksand font-semibold bg-ac text-lt rounded-lg cursor-pointer w-42 h-12 text-center flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-4">Choose File</span>
                    </button>
                </div>
                <div class="flex flex-col items-center justify-content hidden order-1 border-dashed border-2 border-lt1" id="dropzoneUiExt">
                    <button type="button" id="dropzoneUploadExt" class="mx-auto p-4 bg-transparent text-lt1 rounded-lg cursor-pointer h-48 w-32 text-center flex items-center justify-center">
                        <svg class="w-6 h-6 text-lt1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </form>
            <div class="flex flex-col mx-4 mt-2 lg:mt-8 lg:w-3/6">
                <label for="firstRadio" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Watermark Options</label>
                <ul class="flex flex-col lg:flex-row xl:flex-row mt-4 lg:mt-0 mb-4">
                    <li id="firstCol" class="w-full lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                        <input type="text" id="firstInput" style="display: none;" value="watermark">
                        <div class="flex" id="firstChk">
                            <div class="flex items-center h-5">
                                <input id="firstRadio" name="WatermarkOpt" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                            </div>
                            <div class="ml-4">
                                <label for="firstRadio" class="font-semibold text-md text-lt1 font-quicksand" id="firstRadioText">Image</label>
                            </div>
                        </div>
                    </li>
                    <li id="secondCol" class="w-full lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                        <input type="text" id="secondInput" style="display: none;" value="watermark">
                        <div class="flex" id="secondChk">
                            <div class="flex items-center h-5">
                                <input id="secondRadio" name="WatermarkOpt" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                            </div>
                            <div class="ml-4">
                                <label for="secondRadio" class="font-semibold text-md text-lt1 font-quicksand" id="secondRadioText">Text</label>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="mb-4 mt-6" id="wmMainLayout">
                    <div id="wmLayoutImage" class="flex flex-col" style="display:none;">
                        <input id="wmTypeImage" type="radio" name="wmType" value="image" style="display:none;">
                        <div class="mb-6">
                            <label for="wm_file_input" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Image</label>
                            <input class="block font-quicksand text-sm lg:w-4/6 font-medium text-dt1 w-fullcursor-pointer rounded-lg bg-lt1" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/jpg,image/jpeg,image/avif" onfocusin="checkValidation('wm_file_input')" onfocusout="checkValidation('wm_file_input')">
                            <p class="font-quicksand font-normal mt-2 text-sm text-lt3" id="file_input_help">Image (Max. 5 MB)</p>
                        </div>
                        <div class="mb-6">
                            <label for="wmRadioImageLayoutStyleA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Layer</label>
                            <ul class="flex flex-col lg:flex-row mt-2">
                                <li id="wmColImageLayoutStyleA" class="bg-transparent border-2 lg:w-2/6 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageLayoutStyleInputA" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageLayoutStyleA">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageLayoutStyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-wmRadioImageLayoutStyleTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageLayoutStyleA" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageLayoutStyleTextA">Above content</label>
                                    </div>
                                    </div>
                                </li>
                                <li id="wmColImageLayoutStyleB" class="bg-transparent border-2 lg:w-2/6 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageLayoutStyleInputB" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageLayoutStyleB">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageLayoutStyleB" name="watermarkLayoutStyle" value="below" aria-describedby="helper-wmRadioImageLayoutStyleTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageLayoutStyleB" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageLayoutStyleTextB">Below content</label>
                                    </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mb-6">
                            <label for="watermarkPageImage" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Page</label>
                            <input type="text" id="watermarkPageImage" name="watermarkPageImage" class="block font-quicksand text-sm w-full lg:w-4/6 font-medium text-dt1 w-fullcursor-pointer rounded-lg bg-lt1" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onfocusout="checkValidation('watermarkPage')">
                        </div>
                        <div class="mb-6">
                            <label for="wmRadioImageRotationA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Orientation</label>
                            <ul class="flex flex-col lg:flex-row">
                                <li id="wmColImageRotationA" class="bg-transparent border-2 border-lt backdrop-filter lg:w-2/6 backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageRotationInputA" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageRotationA">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageRotationA" name="watermarkRotation" value="0" aria-describedby="helper-wmRadioImageRotationTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageRotationA" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageRotationTextA">0°</label>
                                    </div>
                                    </div>
                                </li>
                                <li id="wmColImageRotationB" class="bg-transparent border-2 border-lt backdrop-filter lg:w-2/6 backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageRotationInputB" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageRotationB">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageRotationB" name="watermarkRotation" value="90" aria-describedby="helper-wmRadioImageRotationTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageRotationB" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageRotationTextB">90°</label>
                                    </div>
                                    </div>
                                </li>
                                <li id="wmColImageRotationC" class="bg-transparent border-2 border-lt backdrop-filter lg:w-2/6 backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageRotationInputC" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageRotationC">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageRotationC" name="watermarkRotation" value="180" aria-describedby="helper-wmRadioImageRotationTextC" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageRotationC" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageRotationTextC">180°</label>
                                    </div>
                                    </div>
                                </li>
                                <li id="wmColImageRotationD" class="bg-transparent border-2 border-lt backdrop-filter lg:w-2/6 backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColImageRotationInputD" style="display: none;" value="wmImage">
                                    <div class="flex" id="wmChkImageRotationD">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioImageRotationD" name="watermarkRotation" value="270" aria-describedby="helper-wmRadioImageRotationTextD" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioImageRotationD" class="font-semibold text-md text-lt1 font-quicksand" id="wmRadioImageRotationTextD">270°</label>
                                    </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mb-6 flex flex-col">
                            <label id="Transparency" class="block mb-2 font-quicksand text-xl font-bold text-pc4" for="watermarkImageTransparency">Opacity</label>
                            <div class="flex flex-row w-full">
                                <input id="watermarkImageTransparency" name="watermarkFontImageTransparency" type="range" min="0" max="100" value="100" step="1" class="w-full lg:w-4/6 h-2 mt-4 accent-ac rounded-lg cursor-pointer" oninput="showVal(this.value,'image')" onchange="showVal(this.value,'image')">
                                <label id="TransparencyValueImage" class="font-semibold text-md text-lt1 ml-3 mt-2.5 font-quicksand" for="watermarkImageTransparency">100 %</label>
                            </div>
                        </div>
                        <div id="isMosaicImageArea" class="mb-6">
                            <div class="flex flex-row">
                                <input id="isMosaicImage" aria-describedby="isMosaicImage" name="isMosaic" type="checkbox" class="h-4 w-4 mt-1 rounded-md border-ac text-ac focus:ring-2 focus:ring-ac">
                                <div class="ml-2 text-sm">
                                    <label for="isMosaicImage" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Mosaic Effects</label>
                                    <p id="isMosaicImageSub" class="block mb-2 font-quicksand text-sm font-semibold text-pc4">It will stamp a 3x3 matrix mosaic of into your document</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="wmLayoutText" style="display: none;">
                        <input id="wmTypeText" type="radio" name="wmType" value="text" style="display:none;"/>
                        <div class="mb-8">
                            <label for="watermarkText" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Text</label>
                            <input type="text" id="watermarkText" name="watermarkText" class="font-quicksand mt-2 block w-full rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" placeholder="Example: Lorem ipsum dolor sit amet" onfocusout="checkValidation('watermarkText')"/>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="watermarkPageText" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Page</label>
                            <input type="text" id="watermarkPageText" name="watermarkPageText" class="font-quicksand mt-2 block w-full rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onfocusout="checkValidation('watermarkPage')"/>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="watermarkFontColor" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Font Color</label>
                            <div class="flex flex-row">
                                <input type="text" id="watermarkFontColor" name="watermarkFontColor" class="font-quicksand block h-10 w-full rounded-lg border border-lt1 bg-lt1 text-xs text-dt1" readonly="">
                                <input id="wmFontColorPicker" class="font-quicksand text-sm font-semibold rounded-lg bg-transparent text-pc4 h-10 w-10 ms-4" type="color" value="#4DAAAA" onfocusout="fontColorValue()">
                            </div>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="wmRadioFontFamilyA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Font Family</label>
                            <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3">
                                <li id="wmColFontFamilyA" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputA" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyA">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyA" name="watermarkFontFamily" value="Arial" aria-describedby="helper-wmRadioFontFamilyTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyA" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextA">Arial</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColFontFamilyB" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputB" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyB">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyB" name="watermarkFontFamily" value="Arial Unicode MS" aria-describedby="helper-wmRadioFontFamilyTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyB" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextB">Arial Unicode MS</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColFontFamilyC" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputC" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyC">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyC" name="watermarkFontFamily" value="Comic Sans MS" aria-describedby="helper-wmRadioFontFamilyTextC" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyC" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextC">Comic Sans MS</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColFontFamilyD" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputD" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyD">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyD" name="watermarkFontFamily" value="Courier" aria-describedby="helper-wmRadioFontFamilyTextD" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyD" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextD">Courier</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColFontFamilyE" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputE" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyE">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyE" name="watermarkFontFamily" value="Times New Roman" aria-describedby="helper-wmRadioFontFamilyTextE" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyE" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextE">Times New Roman</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColFontFamilyF" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                    <input type="text" id="wmColFontFamilyInputF" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkFontFamilyF">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioFontFamilyF" name="watermarkFontFamily" value="Verdana" aria-describedby="helper-wmRadioFontFamilyTextF" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioFontFamilyF" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontFamilyTextF">Verdana</label>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="watermarkFontSize" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Font Size</label>
                            <input type="number" id="watermarkFontSize" name="watermarkFontSize" class="font-poppins mt-4 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" onfocusout="checkValidation('watermarkFontSize')"/>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="wmRadioFontStyleA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Font Style</label>
                            <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                            <li id="wmColFontStyleA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="wmColFontStyleInputA" style="display: none;" value="wmText">
                                <div class="flex" id="wmChkFontStyleA">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioFontStyleA" name="watermarkFontStyle" value="Regular" aria-describedby="helper-wmRadioFontStyleTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioFontStyleA" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontStyleTextA">Regular</label>
                                    </div>
                                </div>
                            </li>
                            <li id="wmColFontStyleB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="wmColFontStyleInputB" style="display: none;" value="wmText">
                                <div class="flex" id="wmChkFontStyleB">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioFontStyleB" name="watermarkFontStyle" value="Bold" aria-describedby="helper-wmRadioFontStyleTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioFontStyleB" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontStyleTextB">Bold</label>
                                    </div>
                                </div>
                            </li>
                            <li id="wmColFontStyleC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="wmColFontStyleInputC" style="display: none;" value="wmText">
                                <div class="flex" id="wmChkFontStyleC">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioFontStyleC" name="watermarkFontStyle" value="Italic" aria-describedby="helper-wmRadioFontStyleTextC" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioFontStyleC" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioFontStyleTextC">Italic</label>
                                    </div>
                                </div>
                            </li>
                            </ul>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="wmRadioLayoutStyleA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Layer</label>
                            <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
                            <li id="wmColLayoutStyleA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="wmRadioLayoutStyleInputA" style="display: none;" value="wmText">
                                <div class="flex" id="wmChkLayoutStyleA">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioLayoutStyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-wmRadioLayoutStyleTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioLayoutStyleA" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioLayoutStyleTextA">Above content</label>
                                    </div>
                                </div>
                            </li>
                            <li id="wmColLayoutStyleB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                <input type="text" id="wmRadioLayoutSytleInputB" style="display: none;" value="wmText">
                                <div class="flex" id="wmChkLayoutStyleB">
                                    <div class="flex h-5 items-center">
                                        <input id="wmRadioLayoutStyleB" name="watermarkLayoutStyle" value="below" aria-describedby="helper-wmRadioLayoutStyleTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                    </div>
                                    <div class="ml-4">
                                        <label for="wmRadioLayoutStyleB" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioLayoutStyleTextB">Below content</label>
                                    </div>
                                </div>
                            </li>
                            </ul>
                        </div>
                        <div class="mb-8 mt-4">
                            <label for="wmRadioRotationA" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Orientation</label>
                            <ul class="grid grid-cols-1 gap-2 xl:grid-cols-4 xl:gap-4">
                                <li id="wmColRotationA" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmRadioRotationInputA" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkRotationA">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioRotationA" name="watermarkRotation" value="0" aria-describedby="helper-wmRadioRotationTextA" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioRotationA" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioRotationTextA">0°</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColRotationB" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmRadioRotationInputB" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkRotationB">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioRotationB" name="watermarkRotation" value="90" aria-describedby="helper-wmRadioRotationTextB" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioRotationB" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioRotationTextB">90°</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColRotationC" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmRadioRotationInputC" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkRotationC">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioRotationC" name="watermarkRotation" value="180" aria-describedby="helper-wmRadioRotationTextC" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioRotationC" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioRotationTextC">180°</label>
                                        </div>
                                    </div>
                                </li>
                                <li id="wmColRotationD" class="border border-slate-200 p-2 mt-2 rounded hover:border-sky-400">
                                    <input type="text" id="wmRadioRotationInputD" style="display: none;" value="wmText">
                                    <div class="flex" id="wmChkRotationD">
                                        <div class="flex h-5 items-center">
                                            <input id="wmRadioRotationD" name="watermarkRotation" value="270" aria-describedby="helper-wmRadioRotationTextD" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0"/>
                                        </div>
                                        <div class="ml-4">
                                            <label for="wmRadioRotationD" class="font-quicksand text-sm font-semibold text-pc4 mt-4 h-10 w-10 ms-4" id="wmRadioRotationTextD">270°</label>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="mb-6 flex flex-col">
                            <label id="Transparency" class="block mb-2 font-quicksand text-xl font-bold text-pc4" for="watermarkTextTransparency">Opacity</label>
                            <div class="flex flex-row w-full">
                                <input id="watermarkTextTransparency" name="watermarkFontTextTransparency" type="range" min="0" max="100" value="100" step="1" class="w-full lg:w-4/6 h-2 mt-4 accent-ac rounded-lg cursor-pointer" oninput="showVal(this.value,'text')" onchange="showVal(this.value,'text')">
                                <label id="TransparencyValueText" class="font-semibold text-md text-lt1 ml-3 mt-2.5 font-quicksand" for="watermarkTextTransparency">100 %</label>
                            </div>
                        </div>
                        <div id="isMosaicImageArea" class="mb-6">
                            <div class="flex flex-row">
                                <input id="isMosaicText" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="h-4 w-4 mt-1 rounded-md border-ac text-ac focus:ring-2 focus:ring-ac">
                                <div class="ml-2 text-sm">
                                    <label for="isMosaicText" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Mosaic Effects</label>
                                    <p id="isMosaicTextSuv" class="block mb-2 font-quicksand text-sm font-semibold text-pc4">It will stamp a 3x3 matrix mosaic of into your document</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div dir="ltr">
                    <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-quicksand font-semibold bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 text-lt1 rounded-lg cursor-pointer w-full h-10" style="display: none" data-ripple-light="true">Watermark PDF</button>
                </div>
                <div class="flex flex-col">
                    @include('includes.alert')
                </div>
            </div>
        </div>
       @stop
    </div>
