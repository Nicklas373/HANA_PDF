<html lang="en">
  <head>
    @include('includes.head-lite')
  </head>
 <body class="bg-origin-padding bg-top bg-scroll bg-cover bg-dt1 bg-no-repeat min-h-screen grid grid-cols-1 lg:grid-cols-2" style="background-image: url('{{ asset('assets/home.avif') }}');">
    @yield('content')
    <script async type="text/javascript" src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
  </body>
</html>
