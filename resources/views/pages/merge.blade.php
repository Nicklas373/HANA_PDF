<!DOCTYPE html>
@extends('layouts.alternate-layout')
@section('content')
<div class="px-4 md:px-12" id="merge">
    <section class="flex flex-wrap items-center justify-start sub-headline-viewport max-w-lg lg:max-w-6xl">
        <div class="text-start mx-6">
            <div class="font-magistral font-bold text-pc4 text-3xl lg:text-7xl mb-4 lg:mb-8">PDF Merge</div>
            <div class="font-quicksand font-light text-md lg:text-3xl text-lt1">Combine multiple PDF files online quickly and securely.</div>
        </div>
    </section>
    <div class="flex flex-col p-2">
        <form action="{{ url('api/v1/file/upload') }}" method="post" class="dropzone flex flex-col lg:flex-row xl:flex-row mx-4 items-center justify-center w-6/6 lg:w-4/6 min-h-96 h-fit lg:h-72 max-h-full lg:overflow-y-auto cursor-pointer bg-lt backdrop-filter backdrop-blur-md rounded-[40px] bg-opacity-15 mb-2" id="dropzoneArea">
            {{ csrf_field() }}
            <div class="flex flex-col items-center justify-content p-4" id="dropzoneUiInit">
                <img class="p-4 h-24 w-24" src="{{ asset('assets/icons/placeholder_pdf.svg') }}">
                <p class="mb-2 text-md text-lt3 font-quicksand font-medium">Drop PDF files here</p>
                <p class="text-xs text-lt3 font-quicksand">Or</p>
                <button type="button" id="dropzoneUploadInit" class="mx-auto mt-2 p-4 text-xs font-quicksand font-semibold bg-ac text-lt rounded-lg cursor-pointer w-42 h-12 text-center flex items-center justify-center">
                    <svg class="w-6 h-6 text-lt1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="ml-4">Choose File</span>
                </button>
            </div>
            <div class="flex flex-col items-center justify-content hidden order-1 border-dashed border-2 border-lt1" id="dropzoneUiExt">
                <button type="button" id="dropzoneUploadExt" class="mx-auto p-4 bg-transparent text-lt1 rounded-lg cursor-pointer h-48 w-32 text-center flex items-center justify-center">
                    <svg class="w-6 h-6 text-lt1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </form>
        <div class="flex flex-col mx-4 mt-8 lg:w-3/6">
            <label for="convertType" class="block mb-2 font-quicksand text-xl font-bold text-pc4">Merge Options</label>
            <div dir="ltl">
                <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-2 lg:mt-4 mb-8 sm:mb-6 font-quicksand font-semibold bg-transparent border-2 border-lt backdrop-filter backdrop-blur-md rounded-lg bg-opacity-50 text-lt1 rounded-lg cursor-pointer w-full lg:w-4/6 h-10" data-ripple-light="true">Merge PDF</button>
            </div>
            <div class="flex flex-col">
                @include('includes.alert')
            </div>
        </div>
    </div>
  @stop
</div>
