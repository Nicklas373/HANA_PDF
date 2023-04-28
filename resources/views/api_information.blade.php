<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EMSITPRO PDF Tools</title>
    <link rel="icon" href="http://103.84.194.194/assets/images/elwilis.png" type="image/icon type">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/@material-tailwind/html@latest/styles/material-tailwind.css"/>
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
    <body class="bg-white dark:bg-slate-900 bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
        <section>
            <div class="py-8 px-4 mx-auto max-w-screen-xl text-center lg:py-16 z-10 relative">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">Learn the technology stack</h1>
                <p class="mb-8 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Meet the front-end and back-end technology to build this website</p>
            </div>
        </section>
        <h1 class="text-3xl font-poppins font-semibold tracking-tight leading-none text-gray-900 mt-10 ms-5 md:mt-10 md:ms-10 md:text-2xl lg:text-3xl dark:text-white">Front-end Technologies</h1>
        <div class="grid grid-cols-1 grid-rows-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 p-4 mb-8">
            <div class="p-2 w-76 h-fit sm:mx-4 md:mx-4 md:mt-0 lg:mx-auto lg:ms-32 lg:h-auto lg:w-3/4 xl:w-fit xl:h-fit xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://laravel.com/">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/laravel.png" alt="" height="125px" width="125px" />
                    <div class="p-5">
                        <h5 class="mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">Laravel Framework</h5>
                        <p class="mb-8 font-poppins font-normal text-gray-700">Open source web application framework with expressive, elegant syntax</p>
                        <p class="xl:mt-10 2xl:mt-4 font-normal text-gray-700">Laravel Version: <span class="bg-red-100 text-red-800 text-sm font-poppins font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-red-900 dark:text-red-300"><b><?php echo app()->version(); ?></b></span></p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:mt-0 sm:h-auto md:mt-0 md:h-100 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 lg:mt-0 xl:w-fit xl:mx-4 xl:h-fit xl:mx-4 xl:mt-0 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://tailwindcss.com/">
                    <img class="rounded-t-lg mx-auto mt-4 p-4" src="/assets/tailwind.png" alt="" height="175px" width="175px" />
                    <div class="p-5">
                        <h5 class="mt-6 mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">Tailwind CSS</h5>
                        <p class="mb-8 font-normal text-gray-700">A utility-first CSS framework packed that can be composed to build any design.</p>
                        <p class="mt-8 xl:mt-10 2xl:mt-4 font-normal text-gray-700">Tailwind CSS Version: <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300"><b>3.3.1</b></span></p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit sm:mx-4 mt-6 md:mt-6 md:mx-4 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:ms-32 xl:w-fit xl:mx-4 xl:mt-0 xl:h-fit 2xl:h-auto bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://flowbite.com/">
                    <img class="rounded-t-lg mx-auto mt-4 p-4" src="/assets/flowbite.png" alt="" height="225px" width="215px" />
                    <div class="p-5">
                        <h5 class="mt-4 mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">Flowbite</h5>
                        <p class="mb-9 xl:mb-6 2xl:mb-9 font-normal text-gray-700">Open-source library of web components built with the utility-first classes from Tailwind CSS.</p>
                        <p class="mt-4 font-normal text-gray-700">Flowbite Version: <span class="bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-blue-900 dark:text-blue-300"><b>1.6.5</b></span></p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:h-100 md:h-auto md:mt-6 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-auto xl:mx-4 xl:h-auto xl:mt-0 xl:h-auto 2xl:h-fit bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://vitejs.dev/">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/vite.png" alt="" height="125px" width="125px" />
                    <div class="p-5">
                        <h5 class="mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">Vite JS</h5>
                        <p class="mb-8 xl:mb-5 2xl:mb-8 font-normal text-gray-700">Frontend build tooling that significantly improves the frontend development experience.</p>
                        <p class="mt-4 sm:mt-14 md:mt-4 font-normal text-gray-700">Vite JS Version: <span class="bg-indigo-100 text-indigo-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-indigo-900 dark:text-indigo-300"><b>4.3.2</b></span></p>
                    </div>
                </a>
            </div>
        </div>
        <h1 class="text-3xl font-poppins font-semibold tracking-tight leading-none text-gray-900 mt-10 ms-5 md:mt-10 md:ms-10 md:text-2xl lg:text-3xl dark:text-white">Back-end Technologies</h1>
        <div class="grid grid-cols-1 grid-rows-1 mb-8 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4 p-4">
            <div class="p-2 w-76 h-fit mt-6 sm:mx-4 sm:mt-4 md:mx-4 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:ms-32 xl:w-auto xl:h-fit xl:mx-4 bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://developer.ilovepdf.com/">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/ilovepdf.png" alt="" height="125px" width="125px" />
                    <div class="p-5">
                        <h5 class="mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">iLovePDF</h5>
                        <p class="mb-9 md:mb-20 lg:mb-20 xl:mb-12 font-normal text-gray-700">Our PDF tools in a REST API for developers</p>
                        <div class="flex justify-between mb-2">
                            <span class="text-base font-medium text-blue-700 dark:text-white">Processed files this month:</span>
                            <span id="progressValue" class="text-sm mt-0.5 font-medium text-blue-700 dark:text-white"><?php include 'ext-php/iLovePDFLimit.php';?></span>
                        </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                            <div id="progressBar" class="bg-blue-600 h-2.5 rounded-full" style="width: 23.5%"></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:mt-4 sm:h-auto md:h-100 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-auto xl:mx-4 xl:h-fit bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://nodejs.org/en">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/nodejs.png" alt="" height="180px" width="180px" />
                    <div class="p-5">
                        <h5 class="mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">Node JS</h5>
                        <p class="mb-5 xl:mb-11 2xl:mb-5 font-normal text-gray-700">a cross-platform, open-source server environment that can run on Windows, Linux, Unix, macOS, and more</p>
                        <p class="mt-4 2xl:mt-0 font-normal text-gray-700">Node Version: <span class="bg-green-100 text-green-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-green-900 dark:text-green-300"><b>18.16.0</b></span></p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit mt-6 sm:mx-4 sm:mt-4 sm:mb-4 md:mx-4 md:mb-16 md:h-auto lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:ms-32 xl:w-auto xl:mx-4 xl:h-fit bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://www.aspose.cloud/">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/aspose.jpg" alt="" height="200px" width="250px" />
                    <div class="p-5">
                        <h5 class="mb-4 mt-8 font-poppins font-semibold text-xl tracking-tight text-gray-900">Aspose Cloud</h5>
                        <p class="mb-13 xl:mb-10 font-normal text-gray-700">RESTful APIs to Create, Edit & Convert over 100 File Formats from any Language, on any Platform</p>
                        <p class="mt-4 2xl:mt-5 font-normal text-gray-700">Aspose Version: <span class="bg-yellow-100 text-yellow-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300"><b>23.3</b></span></p>
                    </div>
                </a>
            </div>
            <div class="p-2 w-76 h-fit sm:h-auto mt-6 mb-4 sm:mt-4 md:h-auto md:mb-16 lg:mb-16 lg:mt-6 lg:w-3/4 lg:h-auto lg:mx-auto lg:me-24 xl:w-auto xl:mx-4 xl:h-auto 2xl:h-fit bg-white border border-gray-200 rounded-lg shadow hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300" type="button" data-ripple-dark="true">
                <a href="https://www.mysql.com/">
                    <img class="rounded-t-lg mx-auto p-4" src="/assets/mysql.png" alt="" height="175px" width="165px" />
                    <div class="p-5">
                        <h5 class="mb-2 font-poppins font-semibold text-xl tracking-tight text-gray-900">MySQL</h5>
                        <p class="mb-12 2xl:mb-10 font-normal text-gray-700">Open-source relational database management system (RDBMS).</p>
                        <p class="mt-16 xl:mt-12 2xl:mt-12 font-normal text-gray-700">MySQL Version: <span class="bg-yellow-100 text-yellow-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded dark:bg-yellow-900 dark:text-yellow-300"><b><?php echo mysqli_get_client_info(); ?></b></span></p>
                    </div>
                </a>
            </div>
        </div>
        <script src="/ext-js/progress.js"></script>
        <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
    </body>
</main>
<footer class="fixed md:inline-flex bottom-0 left-0 z-20 w-full p-4 bg-slate-900 border-t border-text-slate-200 shadow md:flex md:items-center md:justify-between p-0 md:p-2">
    <span class="font-poppins font-semibold rounded text-slate-200">© 2023 <a href="https://elwilis.com/" class="hover:underline">Elwilis</a>. All Rights Reserved.</span>
</footer>
</html>