<html lang="en" class="scroll-smooth">
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
  </head>
  <body class="bg-origin-padding bg-top bg-scroll bg-cover bg-dt1 bg-no-repeat" style="background-image: url('{{ asset('assets/home.avif') }}');">
    @include('includes.modal')
    @include('includes.pdf-preview')
    <div id="content" class="flex flex-col min-h-screen justify-between">
        @yield('content')
    </div>
    <script async type="module" src="https://unpkg.com/pdfjs-dist@4.2.67/build/pdf.mjs"></script>
    <script async type="module" src="https://unpkg.com/pdfjs-dist@4.2.67/build/pdf.worker.mjs"></script>
    <script async type="text/javascript" src="{{asset('ext-js/kao-controller.js')}}"></script>
    <script async type="text/javascript" src="{{asset('ext-js/kao-main.js')}}"></script>
    <script async type="text/javascript" src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
  </body>
  <footer>@include('includes.footer')</footer>
</html>
