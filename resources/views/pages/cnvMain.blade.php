@extends('layouts.alternate-layout')
@section('content')
<div class="px-4 md:px-12">
    <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg mb-4 lg:max-w-6xl">
        <div class="text-start mx-6">
            <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Converter</div>
            <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Convert PDF or document files to other format with our converter.</div>
        </div>
    </section>
    <div class="mx-4 mb-16 mt-32 grid grid-cols-1 gap-8 p-4 lg:grid-cols-3">
        <div class="h-fit w-full rounded-lg px-2 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 md:mt-0 md:h-64 md:w-11/12" data-ripple-light="true" type="button">
            <a href="/cnvToPDF" class="flex flex-row items-center justify-center mx-auto h-full">
                <div dir="rtl" class="h-full w-3/6 md:h-3/6 lg:w-2/6">
                    <img class="p-2 mt-0 h-28 w-28 lg:w-36 lg:h-36" src="{{ asset('assets/icons/to_pdf.svg') }}"/>
                </div>
                <div class="flex flex-col mx-4 w-4/6 sm:w-3/6 md:w-4/6 lg:w-3/6 h-48">
                    <div class="mt-12 2xl:mt-8">
                        <h5 class="font-magistral font-semibold text-md md:text-lg 2xl:text-2xl text-pc4">Convert To PDF</h5>
                    </div>
                    <div class="mt-2 mb-2 md:mt-4 md:mb-4 overflow-auto">
                        <p class="font-quicksand font-normal text-xs md:text-sm 2xl:text-lg text-lt1">Convert document or image files into specified document format.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="h-fit w-full rounded-lg px-2 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 md:mt-0 md:h-64 md:w-11/12" data-ripple-light="true" type="button">
            <a href="/cnvFromPDF" class="flex flex-row items-center justify-center mx-auto h-full">
                <div dir="rtl" class="h-full w-3/6 md:h-3/6 lg:w-2/6">
                    <img class="p-2 mt-0 h-28 w-28 lg:w-36 lg:h-36" src="{{ asset('assets/icons/from_pdf.svg') }}"/>
                </div>
                <div class="flex flex-col mx-4 w-4/6 sm:w-3/6 md:w-4/6 lg:w-3/6 h-48">
                    <div class="mt-12 2xl:mt-8">
                        <h5 class="font-magistral font-semibold text-md md:text-lg 2xl:text-2xl text-pc4">Convert From PDF</h5>
                    </div>
                    <div class="mt-2 mb-2 md:mt-4 md:mb-4 overflow-auto">
                        <p class="font-quicksand font-normal text-xs md:text-sm 2xl:text-lg text-lt1">Convert PDF files into specified document format.</p>
                    </div>
                </div>
            </a>
        </div>
        <div class="h-fit w-full rounded-lg px-2 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 md:mt-0 md:h-64 md:w-11/12" data-ripple-light="true" type="button">
            <a href="/htmltopdf" class="flex flex-row items-center justify-center mx-auto h-full">
                <div dir="rtl" class="h-full w-3/6 md:h-3/6 lg:w-2/6">
                    <img class="p-2 mt-0 h-28 w-28 lg:w-36 lg:h-36" src="{{ asset('assets/icons/html_to_pdf.svg') }}"/>
                </div>
                <div class="flex flex-col mx-4 w-4/6 sm:w-3/6 md:w-4/6 lg:w-3/6 h-48">
                    <div class="mt-12 2xl:mt-8">
                        <h5 class="font-magistral font-semibold text-md md:text-lg 2xl:text-2xl text-pc4">HTML To PDF</h5>
                    </div>
                    <div class="mt-2 mb-2 md:mt-4 md:mb-4 overflow-auto">
                        <p class="font-quicksand font-normal text-xs md:text-sm 2xl:text-lg text-lt1">Convert URL address or web page into PDF format.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>
  @stop
</div>
