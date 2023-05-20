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
    <body class="bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
        <section>
            <div class="py-8 px-4 mt-16 md:mt-24 lg:mt-16 mx-auto max-w-screen-xl text-center lg:py-16 z-0 relative">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">HTML To PDF</h1>
                <p class="mb-8 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Convert URL address or web page into PDF format</p>
            </div>
        </section>
        <div id="loadingModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-2xl max-h-full">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                    <div class="p-6 space-y-6">
                        <p class="font-poppins font-semibold text-gray-900 text-base leading-relaxed text-center">
                            Processing PDF...
                        </p>
                        <svg aria-hidden="true" class="w-16 h-16 text-gray-200 animate-spin fill-blue-600 mx-auto" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <form action="/htmltopdf/web" id="splitForm" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}
            <div class="grid grid-rows-1 p-4" id="grid-layout">
                <div class="p-2 w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 mx-auto bg-white border border-gray-200 rounded-lg shadow">
                    <label for="urlToPDF" class="block mb-2 font-poppins text-sm font-semibold text-gray-900 dark:text-white">Write the website URL:</label>
                    <input type="text" id="urlToPDF" name="urlToPDF" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="https://emsitpro-pdftools.com" required>
                    @if ($message = Session::get('success'))
                        <div class="flex p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div class="ml-3 text-sm font-medium">
                                HTML To PDF Success !, Click <a href="{{ $message }}" class="font-poppins font-semibold underline hover:no-underline">Here </a>to download.
                            </div>
                        </div>
                    @elseif ($message = Session::get('error'))
                        <div class="flex p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div>
                                <span class="font-poppins font-medium">HTML To PDF Error ! </span> {{ $message }}
                            </div>
                        </div>
                    @elseif ($errors->any())
                        {!! implode('', $errors->all(
                            '<div class="flex p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                                <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                <span class="sr-only">Info</span>
                                <div>
                                    <span class="font-poppins font-medium">HTML To PDF Error ! </span> :message
                                </div>
                            </div>'
                            )) !!}
                    @endif
                    </div>
                    <button type="submit" id="submitBtn" data-modal-target="loadingModal" data-modal-toggle="loadingModal" class="mt-4 mb-16 sm:mb-8 md:mb-4 mx-auto font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 h-16 md:h-16 lg:h-16" onClick="onClick()">Convert HTML</button>
                </div>
            </div>
        </form>
        <script src="/ext-js/spinner.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    </body>
    <footer class="fixed bottom-0 left-0 right-0 w-full p-2 bg-slate-900 border-t border-text-slate-200 shadow md:flex md:items-center md:justify-between">
        <span class="font-poppins font-semibold rounded text-slate-200">© 2023 <a href="https://github.com/HANA-CI-Build-Project" class="hover:underline">HANA-CI Build Project</a>. All Rights Reserved.</span>
    </footer>
</html>