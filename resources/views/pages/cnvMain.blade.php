<!DOCTYPE html>
@extends('layouts.alternate-layout') @section('content')
<div class="px-4 md:px-12">
    <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg mb-4 lg:max-w-6xl">
        <div class="text-start mx-6">
            <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Converter</div>
            <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Convert PDF or document files to other format with our converter.</div>
        </div>
    </section>
  <div class="flex flex-col lg:flex-row justify-between items-center">
    <div class="h-fit w-full p-2 lg:w-4/6 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-60" data-ripple-light="true" type="button">
      <a href="/cnvToPDF">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="{{ asset('assets/icons/to_pdf.svg') }}" alt="" height="64px" width="64px" />
        </div>
        <div class="mx-8 py-2 md:mx-4 lg:mx-0 lg:p-2 mt-8 lg:mt-6 lg:mb-4 ">
          <h5 class="font-poppins mb-2 text-xl xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">Convert To PDF</h5>
          <p class="font-poppins mt-4 mb-4 text-xs lg:text-sm text-gray-700">Convert document or image files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-fit w-full p-2 lg:w-4/6 lg:mx-6 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-60" type="button" data-ripple-light="true">
      <a href="/cnvFromPDF">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="{{ asset('assets/icons/from_pdf.svg') }}" alt="" height="64px" width="64px" />
        </div>
        <div class="mx-8 py-2 md:mx-4 lg:mx-0 lg:p-2 mt-8 lg:mt-6 lg:mb-4">
          <h5 class="font-poppins mb-2 text-xl xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">Convert From PDF</h5>
          <p class="font-poppins mt-4 mb-4 text-xs lg:text-sm text-gray-700">Convert PDF files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-fit w-full p-2 lg:w-4/6 bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-60" type="button" data-ripple-light="true">
      <a href="/htmltopdf">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="{{ asset('assets/icons/html_to_pdf.svg') }}" alt="" height="64px" width="64px" />
        </div>
        <div class="mx-8 py-2 md:mx-4 mt-8 lg:mt-6 lg:mb-4">
          <h5 class="font-poppins mb-2 text-xl xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">HTML To PDF</h5>
          <p class="font-poppins mt-4 mb-4 text-xs lg:text-sm text-gray-700">Convert URL address or web page into PDF format</p>
        </div>
      </a>
    </div>
  </div>
  @stop
</div>
