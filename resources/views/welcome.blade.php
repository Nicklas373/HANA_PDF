<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Eureka PDF</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://unpkg.com/@material-tailwind/html@latest/styles/material-tailwind.css"/>
        @vite(['resources/css/app.css','resources/js/app.js'])
        <nav class="bg-slate-900 dark:bg-slate-800 fixed w-full z-20 top-0 left-0 border-b">
            <div class="max-w-screen-xl flex flex-wrap items-center justify-between p-4">
                <a href="/" class="flex items-center">
                    <span class="h-8 self-center text-2xl font-poppins font-semibold text-sky-400">Eureka</span>
                    ''
                    <span class="mr-14 self-center text-2xl font-poppins font-semibold text-slate-200">PDF</span>
                </a>
                <button data-collapse-toggle="navbar-dropdown" type="button" class="inline-flex items-center p-2 text-sm text-slate-200 rounded-lg md:hidden focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="navbar-dropdown" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                </button>
                <div class="w-full md:block md:w-auto md:mt-4 lg:mt-0 hidden" id="navbar-dropdown">
                <ul class="flex flex-col font-medium p-4 md:p-0 mt-2 border border-slate-900 rounded-lg bg-slate-900 md:flex-row md:space-x-8 md:mt-0 md:border-0 dark:bg-slate-800 dark:border-slate-800">
                    <li>
                        <a href="/compress" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">Compress PDF</a>
                    </li>
                    <li>
                        <button id="dropdownNavbarLink" data-dropdown-toggle="dropdownNavbar" class="flex items-center justify-between w-full py-2 pl-3 pr-4 font-poppins font-semibold text-slate-200 rounded md:hover:bg-slate-900 md:border-0 md:hover:text-sky-400 md:p-0 md:w-auto">Convert PDF <svg class="w-5 h-5 ml-1" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></button>
                        <div id="dropdownNavbar" class="z-10 hidden font-poppins font-semibold text-slate-200 bg-slate-900 divide-y divide-text-slate-200 rounded-lg shadow w-44">
                            <ul class="py-2 font-poppins font-semibold text-slate-200" aria-labelledby="dropdownLargeButton">
                                <li>
                                    <a href="/pdftoexcel" class="block px-4 py-2 hover:text-sky-400">PDF To Excel</a>
                                </li>
                                <li>
                                    <a href="/pdftoword" class="block px-4 py-2 hover:text-sky-400">PDF To Word</a>
                                </li>
                                <li>
                                    <a href="/pdftojpg" class="block px-4 py-2 hover:text-sky-400">PDF To JPG</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                    <li>
                        <a href="/merge" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">Merge PDF</a>
                    </li>
                    <li>
                        <a href="/split" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">Split PDF</a>
                    </li>
                    <li>
                        <a href="/watermark" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">Watermark PDF</a>
                    </li>
                    <li>
                        <a href="/api" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">About</a>
                    </li>
                </ul>
                </div>
            </div>
        </nav>
    </head>
    <body class="bg-white dark:bg-slate-900 bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
        <section>
            <div class="py-8 px-4 mt-16 md:mt-24 lg:mt-16 mx-auto max-w-screen-xl text-center lg:py-16 z-0 relative">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">Make great work happen from anywhere</h1>
                <p class="mb-8 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Easily and quickly merge, split, compress, convert, and add watermarks to PDF documents. Powered by <a href="https://www.ilovepdf.com/"><b>iLovePDF</b></a></p>
            </div>
        </section>
        <h1 class="text-3xl font-poppins font-semibold tracking-tight leading-none text-gray-900 mt-10 ms-5 md:mt-10 md:ms-10 md:text-2xl lg:text-3xl dark:text-white">PDF Tools</h1>
        <div class="grid grid-cols-1 grid-rows-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-5 p-4 mb-8">
            <div class="p-2 w-76 h-fit mt-6 sm:mt-0 sm:h-auto md:mt-0 md:h-100 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-fit xl:mx-4 xl:h-auto xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/convert">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/pdf.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">Convert PDF</h5>
                        <p class="font-normal text-gray-700">Convert PDF files into specified document format</p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:mt-0 sm:h-auto sm:mx-4 md:mt-0 md:h-100 lg:w-3/4 lg:h-auto lg:mx-4 xl:w-fit xl:mx-4 xl:h-fit xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/compress">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/compression.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">Compress PDF</h5>
                        <p class="font-normal text-gray-700">Reduce PDF file size while try to keep optimize for best PDF quality</p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:h-auto md:mt-6 md:h-100 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-fit xl:mx-4 xl:h-auto xl:mt-0 xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/merge">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/merge.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">Merge PDF</h5>
                        <p class="font-normal text-gray-700">Combine several PDF in the order from user into one merged PDF file</p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit sm:mx-4 mt-6 md:mt-6 md:mx-4 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-4 xl:w-fit xl:mx-4 xl:mt-0 xl:h-auto 2xl:h-auto bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/split">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/split.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">Split PDF</h5>
                        <p class="font-normal text-gray-700">Separate one page or a whole page into independent PDF files</p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:h-100 md:h-auto md:mt-6 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-auto xl:mx-4 xl:h-auto xl:mt-0 xl:h-auto 2xl:h-fit bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/watermark">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/watermark.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">Watermark PDF</h5>
                        <p class="font-normal text-gray-700">Stamp an image or text over PDF to selected pages or all pages</p>
                    </div>
                </a>
            </div>
        </div>
        <h1 class="text-3xl font-poppins font-semibold tracking-tight leading-none text-gray-900 mt-10 ms-5 md:mt-10 md:ms-10 md:text-2xl lg:text-3xl dark:text-white">Converter Tools</h1>
        <div class="grid grid-cols-1 grid-rows-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-5 p-4 mb-16">
            <div class="p-2 w-76 h-fit mt-6 sm:mt-0 sm:h-auto md:mt-0 md:h-100 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-fit xl:mx-4 xl:h-fit xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="/htmltopdf">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/web.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mb-4 font-poppins font-semibold text-xl tracking-tight text-gray-900">HTML To PDF</h5>
                        <p class="font-normal text-gray-700">Convert URL address or web page into PDF format</p>
                    </div>
                </a>
            </div>
        </div>
        <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    </body>
    <footer class="fixed bottom-0 left-0 right-0 w-full p-2 bg-slate-900 border-t border-text-slate-200 shadow md:flex md:items-center md:justify-between">
        <span class="font-poppins font-semibold rounded text-slate-200">© 2023 <a href="https://github.com/HANA-CI-Build-Project" class="hover:underline">HANA-CI Build Project</a>. All Rights Reserved.</span>
    </footer>
</html>