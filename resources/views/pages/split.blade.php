<!DOCTYPE html>
@extends('layouts.alternate-layout')
@section('content')
    <div class="px-4 md:px-12" id="split">
        <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg lg:max-w-6xl">
            <div class="text-start mx-6">
                <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Split</div>
                <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Split PDF file into separate, smaller and manage right in your choice.</div>
            </div>
        </section>
        <div class="flex flex-col p-2">
            <form action="{{ url('api/v1/file/upload') }}" method="post" class="dropzone flex flex-col lg:flex-row xl:flex-row mx-4 items-center justify-center w-6/6 lg:w-4/6 min-h-96 h-fit lg:h-72 max-h-full lg:overflow-y-auto cursor-pointer bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 mb-2" id="dropzoneAreaSingle">
                {{ csrf_field() }}
                <div class="flex flex-col items-center justify-content p-4" id="dropzoneUiInit">
                    <img class="p-4 h-24 w-24" src="/assets/icons/Placeholder_pdf.svg">
                    <p class="mb-2 text-md text-lt3 font-quicksand font-medium">Drop PDF files here</p>
                    <p class="text-xs text-lt3 font-quicksand">Or</p>
                    <button type="button" id="dropzoneUploadInit" class="mx-auto mt-2 p-4 text-xs font-quicksand font-semibold bg-ac text-lt rounded-lg cursor-pointer w-42 h-12 text-center flex items-center justify-center">
                        <svg class="w-6 h-6 text-lt1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
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
            <div class="flex flex-col mx-4 mt-8 lg:w-3/6">
                <div id="splitLayout1" class="flex flex-col">
                    <label for="firstRadio" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Split Options</label>
                    <ul class="flex flex-col lg:flex-row mb-4">
                        <li id="firstCol" class="w-full p-2 lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                            <input type="text" id="firstInput" class="" style="display: none;" value="split">
                            <div class="flex" id="firstChk">
                                <div class="flex items-center h-5">
                                    <input id="firstRadio" value="split" name="SplitOpt" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                </div>
                                <div class="ml-4">
                                    <label for="firstRadio" class="font-semibold text-md text-lt1 font-quicksand" id="firstRadioText">Split Page</label>
                                </div>
                            </div>
                        </li>
                        <li id="secondCol" class="w-full p-2 lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                            <input type="text" id="secondInput" class="" style="display: none;" value="split">
                            <div class="flex" id="secondChk">
                                <div class="flex items-center h-5">
                                    <input id="secondRadio" value="delete" name="SplitOpt" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                                </div>
                                <div class="ml-4">
                                    <label for="secondRadio" class="font-semibold text-md text-lt1 font-quicksand" id="secondRadioText">Delete Page</label>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <div class="mb-4 mt-6" id="splitLayout2_split" style="display: none;">
                        <label for="thirdRadio" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Page Options</label>
                        <ul id="splitRadio" class="flex flex-col lg:flex-row mb-4">
                            <li id="thirdCol" class="w-full p-2 lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                <input type="text" id="thirdInput" class="" style="display: none;" value="split">
                                <div class="flex" id="thirdChk">
                                    <div class="flex h-5 items-center">
                                        <input id="thirdRadio" value="selPages" name="SplitOpt2" aria-describedby="helper-thirdRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0" onclick="splitLayout3_wthn()" />
                                    </div>
                                    <div class="ml-4">
                                        <label for="thirdRadio" class="font-semibold text-md text-lt1 font-quicksand" id="thirdRadioText">Range</label>
                                    </div>
                                </div>
                            </li>
                            <li id="fourthCol" class="w-full p-2 lg:w-2/6 bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                                <input type="text" id="fourthInput" class="" style="display: none;" value="split">
                                <div class="flex" id="fourthChk">
                                    <div class="flex h-5 items-center">
                                        <input id="fourthRadio" value="cusPages" name="SplitOpt2" aria-describedby="helper-fourthRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0" onclick="splitLayout3_cstm()" />
                                    </div>
                                    <div class="ml-4">
                                        <label for="fourthRadio" class="font-semibold text-md text-lt1 font-quicksand" id="fourthRadioText">Custom</label>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="mb-4 mt-4 lg:mt-6 flex flex-col w-full bg-transparent p-2" id="splitLayout2_delete" style="display: none;">
                        <label for="customPageDelete" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Custom Page</label>
                        <input type="text" id="customPageDelete" name="customPageDelete" class="font-quicksand mt-2 block w-full lg:w-4/6 rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusOut="checkValidation('extCustomPage')" />
                    </div>
                    <div class="mb-4 mt-2 flex flex-col w-full bg-transparent" id="splitLayout3Cstm" style="display: none;">
                        <label for="customPageSplit" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Custom Page</label>
                        <div class="mx-2 lg:w-4/6">
                            <input type="text" id="customPageSplit" name="customPageSplit" class="font-quicksand mt-2 block w-full rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusOut="checkValidation('splitCustomPage')" />
                        </div>
                        <div class="mt-4 mx-2 flex flex-row items-center">
                            <input id="mergePDF1" name="mergePDF" type="checkbox" class="h-4 w-4 rounded-md border-ac text-ac focus:ring-2 focus:ring-ac" />
                            <label for="mergePDF1" class="text-sm mx-4 mt-1 font-normal font-quicksand text-lt1">Merge all Page into one PDF file.</label>
                        </div>
                    </div>
                    <div class="mb-4 mt-2 flex flex-col w-full bg-transparent" id="splitLayout3Wthn" style="display: none;">
                        <div class="flex flex-col lg:flex-row">
                            <div class="mx-2 lg:w-2/6">
                                <label for="fromPage" class="block mb-2 font-quicksand text-xl font-bold text-pc4">First Page</label>
                                <input type="number" id="fromPage" name="fromPage" class="font-quicksand mt-2 block w-full rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="3" placeholder="Example: 1" onFocusOut="checkValidation('splitFirstPage')" />
                            </div>
                            <div class="mx-2 mt-4 lg:w-2/6 lg:mt-0">
                                <label for="toPage" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Last Page</label>
                                <input type="number" id="toPage" name="toPage" class="font-quicksand mt-2 block w-full rounded-lg border border-lt1 bg-lt1 p-2.5 text-xs text-dt1 focus:border-ac focus:ring-ac" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="3" placeholder="Example: 10" onFocusOut="checkValidation('splitLastPage')" />
                            </div>
                        </div>
                         <div class="mt-4 mx-2 flex flex-row items-center">
                            <input id="mergePDF" name="mergePDF" type="checkbox" class="h-4 w-4 rounded-md border-ac text-ac focus:ring-2 focus:ring-ac" />
                            <label for="mergePDF" class="text-sm mx-4 mt-1 font-normal font-quicksand text-lt1">Merge all Page into one PDF file.</label>
                        </div>
                    </div>
                    <div dir="ltl">
                        <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-quicksand font-semibold bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 text-lt1 rounded-lg cursor-pointer w-full lg:w-4/6 h-10" style="display: none;" data-ripple-light="true">Split PDF</button>
                    </div>
                </div>
                <div class="flex flex-col">
                    @include('includes.alert')
                </div>
            </div>
        </div>
       @stop
    </div>
