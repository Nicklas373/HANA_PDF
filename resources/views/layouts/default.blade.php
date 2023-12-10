<!DOCTYPE html>
<html>
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
  </head>
  <body class="bg-cover bg-fixed bg-center bg-no-repeat" style="background-image: url('../assets/whitebg.png');">
    <div>
      <div id="content" class="flex flex-col min-h-screen justify-between">
        @include('includes.modal') @yield('content')
        <script src="/build/assets/kao-logic-aadc2f23.js" type="module"></script>
        <script src="/ext-js/kao-controller.js"></script>
        <script src="/ext-js/kao-main.js"></script>
        <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
      </div>
      <footer>@include('includes.footer')</footer>
    </div>
  </body>
</html>
