@extends('layouts.alternate-layout')
@section('content')
    <div class="px-4 md:px-12" id="compress">
        <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg lg:max-w-6xl">
            <div class="text-start mx-6">
                <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Compress</div>
                <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Create smaller PDF size while trying to keep optimized for quality.</div>
            </div>
        </section>
        <div class="flex flex-col p-2" id="dropzoneCmp">
            <form action="{{ url('api/v1/file/upload') }}" method="post" class="dropzone flex flex-col lg:flex-row xl:flex-row mx-4 items-center justify-center w-6/6 lg:w-4/6 min-h-96 h-fit lg:h-72 max-h-full lg:overflow-y-auto cursor-pointer bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 mb-2" id="dropzoneArea">
                {{ csrf_field() }}
                <div class="flex flex-col items-center justify-content p-4" id="dropzoneUiInit">
                    <img class="p-4 h-24 w-24" src="{{ asset('assets/icons/placeholder_pdf.svg') }}">
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
                <label for="firstRadio" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Compression Quality</label>
                <ul class="flex flex-col lg:flex-row xl:flex-row mt-2 lg:mt-0 mb-4">
                    <li id="firstCol" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                        <input type="text" id="firstInput" class="" style="display: none;" value="comp">
                        <div class="flex" id="firstChk">
                            <div class="flex items-center h-5">
                                <input id="firstRadio" name="compMethod" value="low" aria-describedby="helper-firstRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-ac focus:ring-0">
                            </div>
                            <div class="ml-4">
                                <label for="firstRadio" class="font-semibold text-md text-lt1 font-quicksand" id="firstRadioText">Lowest</label>
                                <p id="helper-firstRadioText" class="text-sm mt-1 font-regular font-quicksand text-lt1">High quality, less compression</p>
                            </div>
                        </div>
                    </li>
                    <li id="secondCol" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                        <input type="text" id="secondInput" class="" style="display: none;" value="comp">
                        <div class="flex" id="secondChk">
                            <div class="flex items-center h-5">
                                <input id="secondRadio" name="compMethod" value="recommended" aria-describedby="helper-secondRadioText" type="radio" class="w-4 h-4 mt-1.5 text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-pc2 focus:ring-0">
                            </div>
                            <div class="ml-4">
                                <label for="secondRadio" class="font-semibold text-md text-lt1 font-quicksand" id="secondRadioText">Recommended</label>
                                <p id="helper-firstRadioText" class="text-sm mt-1 font-regular font-quicksand text-lt1">Good quality, good compression</p>
                            </div>
                        </div>
                    </li>
                    <li id="thirdCol" class="bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 p-2 mt-2 mx-2">
                        <input type="text" id="thirdInput" class="" style="display: none;" value="comp">
                        <div class="flex" id="thirdChk">
                            <div class="flex items-center h-5">
                                <input id="thirdRadio" name="compMethod" value="extreme" aria-describedby="helper-thirdRadioText" type="radio" class="w-4 h-4 mt-1.5  text-ac border-ac ring-ac ring-0 hover:ring-2 hover:ring-pc2 focus:ring-0">
                            </div>
                            <div class="ml-4">
                                <label for="secondRadio" class="font-semibold text-md text-lt1 font-quicksand" id="thirdRadioText">High</label>
                                <p id="helper-firstRadioText" class="text-sm mt-1 font-regular font-quicksand text-lt1">Less quality, high compression</p>
                            </div>
                        </div>
                    </li>
                </ul>
                <div dir="ltl">
                    <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-6 mb-8 sm:mb-6 font-quicksand font-semibold bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 text-lt1 rounded-lg cursor-pointer w-full lg:w-4/6 h-10" data-ripple-light="true">Compress PDF</button>
                </div>
                <div class="flex flex-col">
                    @include('includes.alert')
                </div>
            </div>
        </div>
    @stop
    </div>
