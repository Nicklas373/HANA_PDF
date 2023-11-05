@extends('layouts.default')
@section('content')
    <div class="px-4 md:px-12">
        <section>
            <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 md:text-5xl lg:text-6xl">PDF Convert</h1>
                <p class="mb-4 text-lg font-poppins font-thin text-gray-500 lg:text-2xl">Convert Document or PDF files into specified document format</p>
            </div>
        </section>
        <div class="mx-4 mb-16 gap-8 grid grid-cols-1 p-4 mt-32 sm:grid-cols-3">
            <div class="h-60 md:h-64 px-2 w-full rounded-lg bg-gray-300 backdrop-filter backdrop-blur-md bg-opacity-50 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:w-11/12" data-ripple-dark="true" type="button">
              <a href="/cnvToPDF">
                <div dir="rtl">
                    <img class="p-2 md:p-4 mr-2 mt-4 xl:mt-6 xl:mr-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/document.png" alt="" height="64px" width="64px" />
                </div>
                <div class="p-2 mt-4 xl:mt-6 xl:mb-4">
                    <h5 class="font-poppins mb-2 text-lg xl:text-2xl xl:mb-4 font-semibold tracking-tight text-slate-900">Convert To PDF</h5>
                    <p class="font-poppins text-sm xl:text-base text-gray-700">Convert Document files into specified document format</p>
                </div>
              </a>
            </div>
            <div class="h-60 md:h-64 px-2 w-full rounded-lg bg-gray-300 backdrop-filter backdrop-blur-md bg-opacity-50 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:w-11/12" type="button" data-ripple-dark="true">
              <a href="/cnvFromPDF">
                <div dir="rtl">
                    <img class="p-2 md:p-4 mr-2 mt-4 xl:mt-6 xl:mr-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/pdf-file-format.png" alt="" height="64px" width="64px" />
                </div>
                <div class="p-2 mt-4 xl:mt-6 xl:mb-4">
                    <h5 class="font-poppins mb-2 text-lg xl:text-2xl font-semibold tracking-tight text-slate-900">Convert From PDF</h5>
                    <p class="font-poppins text-sm xl:text-base text-gray-700">Convert PDF files into specified document format</p>
                </div>
              </a>
            </div>
            <div class="h-60 md:h-64 px-2 w-full rounded-lg bg-gray-300 backdrop-filter backdrop-blur-md bg-opacity-50 shadow-[inset_30px_30px_60px_-10px_rgba(255,255,255,1)] hover:shadow-[inset_-30px_-30px_60px_-10px_rgba(255,255,255,1)] hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out md:mt-0 md:w-11/12" type="button" data-ripple-dark="true">
                <a href="/htmltopdf">
                  <div dir="rtl">
                      <img class="p-2 md:p-4 mr-2 mt-4 xl:mt-6 xl:mr-6 xl:p-2 2xl:mt-8 2xl:mr-8 2xl:p-0" src="/assets/web.png" alt="" height="64px" width="64px" />
                  </div>
                  <div class="p-2 mt-4 xl:mt-6 xl:mb-4">
                      <h5 class="font-poppins mb-2 text-lg xl:text-2xl font-semibold tracking-tight text-slate-900">HTML To PDF</h5>
                      <p class="font-poppins text-sm xl:text-base text-gray-700">Convert URL address or web page into PDF format</p>
                  </div>
                </a>
              </div>
          </div>
       @stop
    </div>
