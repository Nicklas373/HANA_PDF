@extends('layouts.default') @section('content')
<div>
  <section>
    <div class="relative z-0 mx-auto mt-4 max-w-screen-xl px-4 py-8 text-center lg:py-16">
      <h1 class="font-poppins mb-4 text-4xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-5xl lg:text-6xl">Learn the technology stack</h1>
      <p class="font-poppins mb-8 text-lg font-normal text-gray-500 dark:text-gray-200 sm:px-16 lg:px-48 lg:text-xl">Meet the front-end and back-end technology to build this website</p>
    </div>
  </section>
  <h1 class="font-poppins mb-5 mt-10 text-center text-3xl font-semibold leading-none tracking-tight text-gray-900 dark:text-white md:text-2xl lg:text-3xl">Our Technology Stack</h1>
  <div class="mx-4 mb-16 grid grid-cols-1 p-4 sm:grid-cols-2 md:mx-auto md:grid-cols-3 md:gap-2 xl:mx-auto xl:grid-cols-4 2xl:mb-12">
    <div class="mx-auto h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:h-80 lg:w-72 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://laravel.com/">
        <img class="mx-auto mt-4 p-4" src="/assets/Laravel.png" alt="" height="125px" width="125px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-4">Laravel Framework</h5>
          <p class="font-poppins mb-4 text-sm text-gray-700 md:mb-12 lg:mb-4 2xl:mb-6">Open source web application framework with expressive, elegant syntax</p>
          <p class="font-poppins text-sm text-gray-700">
            Laravel Version:
            <span class="font-poppins mr-2 rounded bg-red-100 px-2.5 py-0.5 text-sm font-medium text-red-800 dark:bg-red-900 dark:text-red-300"
              ><b><?php echo app()->version(); ?></b></span
            >
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:mt-0 sm:w-72 md:h-96 md:w-64 lg:h-80 lg:w-72 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://tailwindcss.com/">
        <img class="mx-auto mt-8 p-4 sm:mt-12" src="/assets/tailwind.png" alt="" height="175px" width="175px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-6">Tailwind CSS</h5>
          <p class="font-poppins mb-4 text-sm text-gray-700 md:mb-12 lg:mb-4 2xl:mb-6">A utility-first CSS framework packed that can be composed to build any design.</p>
          <p class="font-poppins text-sm text-gray-700">
            Tailwind CSS Version: <span class="mr-2 rounded bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300"><b>3.3.3</b></span>
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:mt-0 md:h-96 md:w-64 lg:h-80 lg:w-72 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://flowbite.com/">
        <img class="mx-auto mt-8 p-4 sm:mt-12" src="/assets/flowbite.png" alt="" height="225px" width="215px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-6">Flowbite</h5>
          <p class="font-poppins mb-4 text-sm text-gray-700 md:mb-7 lg:mb-4 2xl:mb-6">Open-source library of web components built with the utility-first classes from Tailwind CSS.</p>
          <p class="font-poppins text-sm text-gray-700">
            Flowbite Version: <span class="mr-2 rounded bg-blue-100 px-2.5 py-0.5 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300"><b>1.8.1</b></span>
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:mt-6 lg:h-80 lg:w-72 xl:mt-0 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://vitejs.dev/">
        <img class="mx-auto mt-4 p-4" src="/assets/vite.png" alt="" height="125px" width="125px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-6 md:mt-0 lg:mt-2 xl:mt-4">Vite JS</h5>
          <p class="font-poppins lg:md-3 mb-3 text-sm text-gray-700 md:mb-12 lg:mb-4 2xl:mb-6">Frontend build tooling that significantly improves the frontend development experience.</p>
          <p class="font-poppins text-sm text-gray-700">
            Vite JS Version: <span class="mr-2 rounded bg-indigo-100 px-2.5 py-0.5 text-sm font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300"><b>4.4.9</b></span>
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:mt-6 lg:h-80 lg:w-72 2xl:mt-6 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://developer.ilovepdf.com/">
        <img class="mx-auto mt-4 p-4" src="/assets/ilovepdf.png" alt="" height="125px" width="125px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-2 md:mt-0 lg:mt-2">iLovePDF</h5>
          <p class="font-poppins mb-4 text-sm text-gray-700 md:mb-16 lg:mb-4 2xl:mb-6">Our PDF tools in a REST API for developers</p>
          <div class="mb-2 flex justify-between">
            <span class="font-poppins text-sm text-blue-700 dark:text-white">Processed files this month:</span>
            <span id="progressValue" class="font-poppins mt-0.5 text-sm text-blue-700 dark:text-white"><?php include 'public/ext-php/iLovePDFLimit.php';?></span>
          </div>
          <div class="h-2.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
            <div id="progressBar" class="h-2.5 rounded-full bg-blue-600" style="width: 23.5%"></div>
          </div>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:mt-6 lg:h-80 lg:w-72 2xl:mt-6 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://nodejs.org/en">
        <img class="mx-auto p-4 sm:mt-4" src="/assets/nodejs.png" alt="" height="180px" width="180px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-2 md:mt-0">Node JS</h5>
          <p class="font-poppins lg:md-4 mb-4 text-sm text-gray-700 md:mb-12 lg:mb-2 xl:mb-2 2xl:mb-4">a cross-platform, open-source server environment that can run on Windows, Linux, Unix, macOS, and more</p>
          <p class="font-poppins text-sm text-gray-700">
            Node Version: <span class="mr-2 rounded bg-green-100 px-2.5 py-0.5 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-300"><b>18.16.0</b></span>
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:mt-6 lg:h-80 lg:w-72 2xl:mt-6 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://www.aspose.cloud/">
        <img class="mx-auto mt-6 p-4" src="/assets/aspose.jpg" alt="" height="200px" width="250px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-8 xl:mt-6">Aspose Cloud</h5>
          <p class="font-poppins mb-6 text-sm text-gray-700 md:mb-4 lg:mb-6">RESTful APIs to Create, Edit & Convert over 100 File Formats from any Language, on any Platform</p>
          <p class="font-poppins mt-8 text-sm text-gray-700 md:mt-12 lg:mt-6 2xl:mt-9">
            Aspose Version: <span class="mr-2 rounded bg-yellow-100 px-2.5 py-0.5 text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"><b>23.7</b></span>
          </p>
        </div>
      </a>
    </div>
    <div class="mx-auto mt-6 h-80 w-full rounded-lg border border-gray-200 bg-white shadow hover:scale-105 hover:transform-gpu hover:transition hover:delay-150 hover:duration-300 hover:ease-in-out sm:mx-auto sm:w-72 md:h-96 md:w-64 lg:mt-6 lg:h-80 lg:w-72 2xl:mt-6 2xl:w-96" type="button" data-ripple-dark="true">
      <a href="https://www.mysql.com/">
        <img class="mx-auto mt-4 p-4" src="/assets/mysql.png" alt="" height="175px" width="165px" />
        <div class="p-4">
          <h5 class="font-poppins mb-2 text-xl font-semibold tracking-tight text-gray-900 sm:mt-4 md:mt-2 xl:mt-0">MySQL</h5>
          <p class="font-poppins mb-6 text-sm text-gray-700 sm:mb-12 md:mb-4 lg:mb-6">Open-source relational database management system (RDBMS).</p>
          <p class="font-poppins mt-8 text-sm text-gray-700 md:mt-16 lg:mt-12 xl:mt-12 2xl:mt-9">
            MySQL Version:
            <span class="mr-2 rounded bg-yellow-100 px-2.5 py-0.5 text-sm font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"
              ><b>8.2.26</b></span
            >
          </p>
        </div>
      </a>
    </div>
  </div>
  <script src="/ext-js/progress.js"></script>
  @stop
</div>
