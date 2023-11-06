<!DOCTYPE html>
<html>
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
    <script src="/ext-js/dropdown.js"></script>
  </head>
  <body style="background-image: url('../assets/whitebg.png');">
    <div>
      <div id="content">
        @yield('content')
        <script src="/build/assets/flowbite-44f515b4.js" type="module"></script>
        <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
      </div>
      <footer>@include('includes.footer')</footer>
    </div>
  </body>
</html>
