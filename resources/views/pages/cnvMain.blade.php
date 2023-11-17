<!DOCTYPE html>
@extends('layouts.default') @section('content')
<div class="px-4 md:px-12">
  <section>
    <div class="z-0 mt-24 max-w-screen-xl px-4 py-8">
      <h1 class="font-poppins mb-4 text-4xl font-semibold leading-none tracking-tight text-sky-400 md:text-5xl lg:text-6xl">PDF Convert</h1>
      <p class="font-poppins mb-4 text-lg font-thin text-gray-500 lg:text-2xl">Convert Document or PDF files into specified document format</p>
    </div>
  </section>
  <div class="mx-4 mb-16 mt-32 grid grid-cols-1 gap-8 p-4 sm:grid-cols-3">
    <div class="h-60 w-full rounded-lg bg-gray-300 bg-opacity-50 px-2 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" data-ripple-dark="true" type="button">
      <a href="/cnvToPDF">
        <div dir="rtl">
          <img class="mr-2 mt-4 p-2 md:p-4 xl:mr-6 xl:mt-6 xl:p-2 2xl:mr-8 2xl:mt-8 2xl:p-0" src="/assets/document.png" alt="" height="64px" width="64px" />
        </div>
        <div class="mt-4 p-2 xl:mb-4 xl:mt-6">
          <h5 class="font-poppins mb-2 text-lg font-semibold tracking-tight text-slate-900 xl:mb-4 xl:text-2xl">Convert To PDF</h5>
          <p class="font-poppins text-sm text-gray-700 xl:text-base">Convert Document files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-60 w-full rounded-lg bg-gray-300 bg-opacity-50 px-2 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" type="button" data-ripple-dark="true">
      <a href="/cnvFromPDF">
        <div dir="rtl">
          <img class="mr-2 mt-4 p-2 md:p-4 xl:mr-6 xl:mt-6 xl:p-2 2xl:mr-8 2xl:mt-8 2xl:p-0" src="/assets/pdf-file-format.png" alt="" height="64px" width="64px" />
        </div>
        <div class="mt-4 p-2 xl:mb-4 xl:mt-6">
          <h5 class="font-poppins mb-2 text-lg font-semibold tracking-tight text-slate-900 xl:text-2xl">Convert From PDF</h5>
          <p class="font-poppins text-sm text-gray-700 xl:text-base">Convert PDF files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="h-60 w-full rounded-lg bg-gray-300 bg-opacity-50 px-2 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] backdrop-blur-md backdrop-filter hover:scale-105 hover:transform-gpu hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:h-64 md:w-11/12" type="button" data-ripple-dark="true">
      <a href="/htmltopdf">
        <div dir="rtl">
          <img class="mr-2 mt-4 p-2 md:p-4 xl:mr-6 xl:mt-6 xl:p-2 2xl:mr-8 2xl:mt-8 2xl:p-0" src="/assets/web.png" alt="" height="64px" width="64px" />
        </div>
        <div class="mt-4 p-2 xl:mb-4 xl:mt-6">
          <h5 class="font-poppins mb-2 text-lg font-semibold tracking-tight text-slate-900 xl:text-2xl">HTML To PDF</h5>
          <p class="font-poppins text-sm text-gray-700 xl:text-base">Convert URL address or web page into PDF format</p>
        </div>
      </a>
    </div>
  </div>
  @stop
</div>
