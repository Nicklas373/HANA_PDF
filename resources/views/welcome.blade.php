<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EMSITPRO PDF Tools</title>
    <link rel="icon" href="public/assets/elwilis.png" type="image/icon type">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/@material-tailwind/html@latest/styles/material-tailwind.css" />
    <link rel="stylesheet" href="{{ asset('build/assets/app-e405037d.css') }}" />
    <link rel="script" href="{{ asset('build/assets/app-547abec6.js') }}" />
    <nav class="fixed left-0 top-0 z-20 w-full border-b bg-slate-900 dark:bg-slate-800">
      <div class="flex max-w-screen-xl flex-wrap items-center justify-between p-4">
        <a href="/" class="flex items-center">
          <img src="public/assets/elwilis.png" class="h-8 mr-3" alt="Elwilis Logo" />
          <span class="self-center ml-4 text-xl font-poppins text-slate-200 dark:text-gray-100">EMSITPRO PDF Tools</span>
        </a>
        <button data-collapse-toggle="navbar-dropdown" type="button" class="inline-flex items-center rounded-lg p-2 text-sm text-slate-200 focus:outline-none focus:ring-2 focus:ring-gray-200 md:hidden" aria-controls="navbar-dropdown" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <svg class="h-6 w-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
        </button>
        <div class="hidden w-full md:mt-4 md:block md:w-auto lg:mt-0" id="navbar-dropdown">
          <ul class="mt-2 flex flex-col rounded-lg border border-slate-900 bg-slate-900 p-4 font-medium dark:border-slate-800 dark:bg-slate-800 md:mt-0 md:flex-row md:space-x-8 md:border-0 md:p-0">
            <li>
              <a href="/compress" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">Compress PDF</a>
            </li>
            <li>
              <a href="/convert" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">Convert PDF</a>
            </li>
            <li>
              <a href="/merge" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">Merge PDF</a>
            </li>
            <li>
              <a href="/split" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">Split PDF</a>
            </li>
            <li>
              <a href="/watermark" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">Watermark PDF</a>
            </li>
            <li>
              <a href="/api" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-200 hover:text-sky-400 md:p-0" aria-current="page">About</a>
            </li>
          </ul>
        </div>
      </div>
    </nav>
  </head>
  <body class="bg-white bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-slate-900 dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
    <section>
      <div class="relative z-0 mx-auto mt-28 sm:mt-16 max-w-screen-xl px-4 py-8 text-center md:mt-24 lg:mt-16 lg:py-16">
        <h1 class="font-poppins mb-4 text-4xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">Make great work happen from anywhere</h1>
        <p class="font-poppins mb-8 text-lg font-normal text-gray-500 dark:text-gray-200 sm:px-16 lg:px-48 lg:text-xl">
          Easily and quickly merge, split, compress, convert, and add watermarks to PDF documents. Powered by <a href="https://www.ilovepdf.com/"><b>iLovePDF</b></a>
        </p>
      </div>
    </section>
    <h1 class="font-poppins mt-10 mb-5 text-center text-3xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-2xl lg:text-3xl">Our PDF Tools</h1>
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
    <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
  </body>
  <footer class="border-text-slate-200 fixed bottom-0 left-0 right-0 w-full border-t bg-slate-900 p-2 shadow md:flex md:items-center md:justify-between">
    <span class="font-poppins rounded font-semibold text-slate-200">© 2023 <a href="https://github.com/HANA-CI-Build-Project" class="hover:underline">HANA-CI Build Project</a>. All Rights Reserved.</span>
  </footer>
</html>
