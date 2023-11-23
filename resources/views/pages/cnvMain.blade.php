<!DOCTYPE html>
@extends('layouts.default') @section('content')
<div class="px-4 md:px-12">
  <section>
    <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
      <h1 class="mb-4 mt-6 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 sm:mt-0 lg:text-6xl">PDF Convert</h1>
      <p class="mb-4 text-base font-poppins font-thin text-gray-500 lg:text-2xl">Convert Document or PDF files into specified document format</p>
    </div>
  </section>
  <div class="mx-4 mb-16 mt-32 grid grid-cols-1 gap-8 p-4 md:grid-cols-3">
    <div class="h-fit w-full rounded-lg bg-gray-300 bg-opacity-5 px-2 border border-white shadow-[inset_10px_10px_40px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-10px_-10px_40px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" data-ripple-light="true" type="button">
      <a href="/cnvToPDF">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/document.png" alt="" height="64px" width="64px" />
        </div>
        <div class="mx-8 py-2 md:mx-4 lg:mx-0 lg:p-2 mt-8 lg:mt-6 lg:mb-4">
          <h5 class="font-poppins mb-2 text-xl xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">Convert To PDF</h5>
          <p class="font-poppins mt-4 mb-4 text-xs lg:text-sm text-gray-700">Convert Document files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-fit w-full rounded-lg bg-gray-300 bg-opacity-5 px-2 border border-white shadow-[inset_10px_10px_40px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-10px_-10px_40px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" type="button" data-ripple-light="true">
      <a href="/cnvFromPDF">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/pdf-file-format.png" alt="" height="64px" width="64px" />
        </div>
        <div class="mx-8 py-2 md:mx-4 lg:mx-0 lg:p-2 mt-8 lg:mt-6 lg:mb-4">
          <h5 class="font-poppins mb-2 text-xl xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">Convert From PDF</h5>
          <p class="font-poppins mt-4 mb-4 text-xs lg:text-sm text-gray-700">Convert PDF files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-fit w-full rounded-lg bg-gray-300 bg-opacity-5 px-2 border border-white shadow-[inset_10px_10px_40px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-10px_-10px_40px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" type="button" data-ripple-light="true">
      <a href="/htmltopdf">
        <div dir="rtl">
          <img class="p-2 mt-4 mr-6 xl:mt-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/web.png" alt="" height="64px" width="64px" />
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
