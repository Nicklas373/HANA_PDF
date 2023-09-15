@extends('layouts.default') @section('content')
<div>
  <section>
    <div class="relative z-0 mx-auto mt-4 max-w-screen-xl px-4 py-8 text-center lg:py-16">
      <h1 class="font-poppins mb-4 text-4xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">Make great work happen from anywhere</h1>
      <p class="font-poppins mb-8 text-lg font-normal text-gray-500 dark:text-gray-200 sm:px-16 lg:px-48 lg:text-xl">
        Easily and quickly merge, split, compress, convert, and add watermarks to PDF documents. Powered by <a href="https://www.ilovepdf.com/"><b>iLovePDF</b></a>
      </p>
    </div>
  </section>
  <h1 class="font-poppins mb-5 mt-10 text-center text-3xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-2xl lg:text-3xl">Our PDF Tools</h1>
  <div class="mx-4 mb-16 grid grid-cols-1 p-4 sm:grid-cols-2 md:mx-auto md:grid-cols-3 lg:mx-0 lg:grid-cols-4 xl:grid-cols-5 2xl:mb-0 2xl:grid-cols-6">
    <div class="mx-auto h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:w-72 md:w-11/12 lg:mx-auto" type="button" data-ripple-dark="true">
      <a href="/convert">
        <img class="mt-4 p-2" src="/assets/pdf.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">Convert PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Convert PDF files into specified document format</p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:mt-0 sm:w-72 md:w-11/12 lg:mx-auto" type="button" data-ripple-dark="true">
      <a href="/compress">
        <img class="mt-4 p-2" src="/assets/compression.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">Compress PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Reduce PDF file size while try to keep optimize for best PDF quality</p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:mt-6 sm:w-72 md:mt-0 md:w-11/12 lg:mx-auto" type="button" data-ripple-dark="true">
      <a href="/merge">
        <img class="mt-4 p-2" src="/assets/merge.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">Merge PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Combine several PDF in the order from user into one merged PDF file</p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:mt-6 sm:w-72 md:mt-6 md:w-11/12 lg:mx-auto lg:mt-0" type="button" data-ripple-dark="true">
      <a href="/split">
        <img class="mt-4 p-2" src="/assets/split.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">Split PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Separate one page or a whole page into independent PDF files</p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:mt-6 sm:w-72 md:mt-6 md:w-11/12 lg:mx-auto lg:mt-6 xl:mt-0" type="button" data-ripple-dark="true">
      <a href="/watermark">
        <img class="mt-4 p-2" src="/assets/watermark.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">Watermark PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Stamp an image or text over PDF to selected pages or all pages</p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-64 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-0 sm:w-72 md:w-11/12 lg:mx-auto 2xl:mt-0" type="button" data-ripple-dark="true">
      <a href="/htmltopdf">
        <img class="mt-4 p-2" src="/assets/web.png" alt="" height="100px" width="100px" />
        <div class="p-4">
          <h5 class="font-poppins mb-4 text-xl font-semibold tracking-tight text-gray-900">HTML To PDF</h5>
          <p class="font-poppins text-sm text-gray-700">Convert URL address or web page into PDF format</p>
        </div>
      </a>
    </div>
  </div>
  @stop
</div>
