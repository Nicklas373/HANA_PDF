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
    <script async type="module" src="{{asset('build/assets/kao-logic-Diab8fFv.js')}}"></script>
    <script async type="text/javascript" src="{{asset('ext-js/kao-controller.js')}}"></script>
    <script async type="text/javascript" src="{{asset('ext-js/kao-main.js')}}"></script>
    <script async type="text/javascript" src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
  </body>
</html>
