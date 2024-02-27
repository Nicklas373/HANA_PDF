<html lang="en">
@extends('layouts.clean-layout')
@section('content')
<div id="500">
    <section class="flex flex-col justify-center items-center bg-dt1 bg-opacity-35 backdrop-filter backdrop-blur-md w-full h-full">
        <div class="text-center px-2">
            <h1 class="text-5xl lg:text-8xl font-semibold font-quicksand text-lt1">500</h1>
        </div>
        <div class="text-center px-4">
            <h2 class="mt-4 lg:mt-8 text-lg lg:text-3xl font-medium font-quicksand text-lt2">Service Temporary Unavailable</h2>
            <h2 class="mt-4 text-xs text-start lg:text-lg font-medium font-quicksand text-lt4">Our service are temporary down or under going maintenance</h2>
            <h2 class="text-xs lg:text-lg text-center font-medium font-quicksand text-lt4">Please wait and try again later.</h3>
        </div>
        <div class="mt-10 px-4">
            <button type="button" id="submitBtn_1" class="p-4 font-quicksand font-medium bg-rt1 text-lt1 rounded-lg cursor-pointer w-full h-14" onclick="window.location.href='/'" data-ripple-light="true">Go Back Home</button>
        </div>
    </section>
</div>
@stop
