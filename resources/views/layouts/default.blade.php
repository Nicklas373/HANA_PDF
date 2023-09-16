<!DOCTYPE html>
<html>
  <head>
    @include('includes.head')
    <header>@include('includes.header')</header>
  </head>
  <body class="bg-white bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-slate-900 dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
    <div>
      <div>
        @yield('content')
        <script src="https://unpkg.com/@material-tailwind/html@latest/scripts/ripple.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.8.1/flowbite.min.js"></script>
      </div>
      <footer>@include('includes.footer')</footer>
    </div>
  </body>
</html>
