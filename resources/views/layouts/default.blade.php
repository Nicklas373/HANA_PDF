<!DOCTYPE html>
<html>
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
  </head>
  <body class="bg-cover bg-fixed bg-center bg-no-repeat" style="background-image: url('{{ asset('assets/whitebg.avif') }}');">
    <div>
      <div id="content" class="flex flex-col min-h-screen justify-between">
        @include('includes.modal') @yield('content')
        <script async src="/ext-js/kao-controller.js"></script>
        <script async src="/ext-js/kao-main.js"></script>
        <script async src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
      </div>
      <footer>@include('includes.footer')</footer>
    </div>
  </body>
</html>
