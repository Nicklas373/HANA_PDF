<!DOCTYPE html>
@extends('layouts.alternate-layout')
@section('content')
<div class="px-4 md:px-12" id="html">
    <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg lg:max-w-6xl">
        <div class="text-start mx-6">
            <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">HTML to PDF</div>
            <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Convert web pages into PDF documents.</div>
        </div>
    </section>
  <form method="POST" enctype="multipart/form-data" class="flex flex-col p-2">
    {{ csrf_field() }}
    <div class="w-full p-2 lg:w-3/6 bg-transparent rounded-lg bg-opacity-50 p-2 mt-2 lg:mx-2">
        <label for="convertType" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Write the Website URL</label>
        <div class="flex">
            <span class="inline-flex items-center px-3 text-sm text-ac bg-ac border border-ac rounded-s-lg">
                <img class="h-6 w-6 text-lt1" src="{{ asset('assets/icons/website.svg') }}" />
            </span>
            <input type="text" id="urlToPDF" name="urlToPDF" class="flowbite-drop-zone font-poppins rounded-r-lg block w-full cursor-pointer border border-gray-300 text-sm text-slate-900 shadow-inner focus:ring-sky-400" onfocusin="checkValidation('urlToPDF')" onfocusout="checkValidation('urlToPDF')" placeholder="https://pdf.hana-ci.com" />
        </div>
    </div>
    <div class="flex flex-col mt-2 w-full lg:mx-4 lg:w-3/6">
        <div dir="ltl">
            <button type="button" id="submitBtn" class="mx-auto mt-6 mb-8 sm:mb-6 font-quicksand font-semibold bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 text-lt1 rounded-lg cursor-pointer w-full lg:w-4/6 h-10" data-ripple-light="true">Convert to PDF</button>
        </div>
        <div class="flex flex-col">
            @include('includes.alert')
        </div>
    </div>
  </form>
  @stop
</div>
