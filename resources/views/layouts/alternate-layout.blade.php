<html lang="en">
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
  </head>
  <body class="bg-origin-padding bg-top bg-scroll bg-dt1 bg-no-repeat" style="background-image: url('{{ asset('assets/home.avif') }}');">
    @include('includes.modal')
    @include('includes.pdf-preview')
    <div id="content" class="flex flex-col min-h-screen justify-between">
        @yield('content')
    </div>
<<<<<<< HEAD
=======
    <script async src="/build/assets/kao-logic-D2YWooi_.js" type="module"></script>
>>>>>>> 65f31d1 (Treewide: Minor changes)
    <script async type="text/javascript" src="/ext-js/kao-controller.js"></script>
    <script async type="text/javascript" src="/ext-js/kao-main.js"></script>
    <script async type="text/javascript" src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
    <script async type="module" src="/pdfjs/build/pdf.mjs"></script>
    <script async type="module" src="/pdfjs/build/pdf.worker.mjs"></script>
  </body>
  <footer>@include('includes.footer')</footer>
</html>
