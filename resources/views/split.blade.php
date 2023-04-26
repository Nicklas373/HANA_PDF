<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EMSITPRO PDF Tools</title>
    <link rel="icon" href="http://103.84.194.194/assets/images/elwilis.png" type="image/icon type">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
    @vite('resources/css/app.css')
    <nav class="bg-slate-900 dark:bg-slate-800">
        <div class="max-w-screen-xl flex flex-wrap items-center justify-between p-4">
            <a href="/" class="flex items-center">
                <img src="http://103.84.194.194/assets/images/elwilis.png" class="h-8 mr-3" alt="Elwilis Logo" />
                <span class="self-center ml-4 text-xl font-poppins text-slate-200 dark:text-gray-100">EMSITPRO PDF Tools</span>
            </a>
            <button data-collapse-toggle="navbar-dropdown" type="button" class="inline-flex items-center p-2 mt-3 text-sm text-slate-200 rounded-lg md:hidden focus:outline-none focus:ring-2 focus:ring-gray-200" aria-controls="navbar-dropdown" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
            </button>
            <div class="hidden w-full md:block md:w-auto sm:mt-4 xl:mt-0" id="navbar-dropdown">
            <ul class="flex flex-col font-medium p-4 md:p-0 mt-2 border border-slate-900 rounded-lg bg-slate-900 md:flex-row md:space-x-8 md:mt-0 md:border-0 dark:bg-slate-800 dark:border-slate-800">
                <li>
                    <a href="/compress" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">Compress PDF</a>
                </li>
                <li>
                    <button id="dropdownNavbarLink" data-dropdown-toggle="dropdownNavbar" class="flex items-center justify-between w-full py-2 pl-3 pr-4 font-poppins font-semibold text-slate-200 rounded md:hover:bg-slate-900 md:border-0 md:hover:text-slate-200 md:p-0 md:w-auto">Convert PDF <svg class="w-5 h-5 ml-1" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg></button>
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
                            <li>
                                <a href="/htmltopdf" class="block px-4 py-2 hover:text-sky-400">HTML To PDF</a>
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
                    <a href="/api" class="block py-2 pl-3 pr-4 font-poppins font-semibold rounded text-slate-200 md:p-0 hover:text-sky-400" aria-current="page">API Information</a>
                </li>
            </ul>
            </div>
        </div>
    </nav>
</head>
<main>
    <form action="*" id="splitForm" method="POST" enctype="multipart/form-data">
	{{ csrf_field() }}
        <body class="bg-white dark:bg-slate-900 bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
            <section>
                <div class="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16 z-10 relative">
                    <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">PDF Split</h1>
                    <p class="mb-8 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Separate one page or a whole page into independent PDF files</p>
                </div>
            </section>
            <div role="status" id="spinner" class="absolute -translate-x-1/2 -translate-y-1/2 top-1/2 left-1/2 invisible">
                <svg aria-hidden="true" class="w-16 h-16 text-gray-200 animate-spin fill-blue-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
            </div>
            <div class="grid grid-rows-1 gap-2 p-4 mb-12 md:mb-0 xl:mb-12 2xl:mb-0" id="grid-layout">
                <div class="p-4 lg:p-2 w-full md:w-2/5 h-fit mx-auto bg-white border border-gray-200 rounded-lg shadow">
                    <label class="block mb-2 font-poppins text-sm font-medium text-slate-900 dark:text-white" for="file_input">Upload PDF file</label>
                    <input class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" aria-describedby="file_input_help" id="file_input" name="file" type="file" accept="application/pdf">
                    <p class="mt-1 font-poppins text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PDF (Max. 25 MB).</p>
                    @if($message = Session::get('success'))
                        <br>
                        <div class="flex p-4 mb-4 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div class="ml-3 text-sm font-medium">
                                Split PDF Success !, Click <a href="{{ $message }}" class="font-poppins font-semibold underline hover:no-underline">Here </a>to download.
                            </div>
                        </div>
                    @elseif($message = Session::get('error'))
                        <br>
                        <div class="flex p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div>
                                <span class="font-poppins font-medium">Split PDF Error ! </span> {{ $message }}.
                            </div>
                        </div>
                    @elseif ($errors->any())
                        <br>
                        {!! implode('', $errors->all(
                            '<div class="flex p-4 mb-4 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                                <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                <span class="sr-only">Info</span>
                                <div>
                                    <span class="font-poppins font-medium">Split PDF Error ! </span> :message.
                                </div>
                            </div>'
                        )) !!}
                    @endif
                </div>
                <div class="text-center rounded-md mt-4 mb-8 lg:mb-0" role="group">
                    <button type="button" class="px-4 py-2 font-poppins font-medium text-slate-200 bg-slate-900 border border-blue-700 rounded-l-lg hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout2_split()">
                        Split by ranges
                    </button>
                    <button type="button" class="px-4 py-2 font-poppins font-medium text-slate-200 bg-slate-900 border border-blue-700 rounded-r-md hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout2_extract()">
                        Extract pages
                    </button>
                </div>
                <div id="splitLayout2"></div>
                <div id="splitLayout3"></div>
            </div>
            <script src="/ext-js/split.js"></script>
            <script src="/ext-js/spinner.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
        </body>
    </form>
</main>
<footer class="fixed md:inline-flex bottom-0 left-0 z-20 w-full p-4 bg-slate-900 border-t border-text-slate-200 shadow md:flex md:items-center md:justify-between p-0 md:p-2">
    <span class="font-poppins font-semibold rounded text-slate-200">© 2023 <a href="https://elwilis.com/" class="hover:underline">Elwilis</a>. All Rights Reserved.</span>
</footer>
</html>