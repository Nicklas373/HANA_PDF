@extends('layouts.default')
@section('content')
    <div class="px-4 md:px-12">
        <section>
            <div class="py-8 px-4 mt-24 max-w-screen-xl z-0">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-sky-400 md:text-5xl lg:text-6xl">HTML To PDF</h1>
                <p class="mb-4 text-lg font-poppins font-thin text-gray-500 lg:text-2xl">Convert URL address or web page into PDF format</p>
            </div>
        </section>
        @include('includes.modal')
        <form action="/htmltopdf/web" id="splitForm" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="grid grid-columns-3 gap-4 p-4 mx-auto mb-8" id="grid-layout">
                <div class="grid md:grid-cols-2 gap-4 md:gap-20">
                    <div>
                        <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Enter the Website URL</label>
                        <input type="text" id="urlToPDF" name="urlToPDF" class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg shadow-inner cursor-pointer" placeholder="https://pdf.hana-ci.com" required>
                        @if ($message = Session::get('stats'))
                        <div id="alert-additional-content-3" class="p-4 mt-4 mb-2 text-green-800 border border-green-300 rounded-lg bg-green-50" role="alert">
                            <div class="flex items-center">
                                <svg class="flex-shrink-0 w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Info</span>
                                <h3 class="text-sm font-poppins">URL has successfully converted !</h3>
                                <br><br>
                            </div>
                            <div class="flex">
                                <button type="button" class="text-green-50 bg-green-800 focus:ring-4 focus:outline-none focus:ring-green-100 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center">
                                    <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"> <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path> <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path> </svg>
                                    <b><a href="{{ session('res') }}">Download PDF</a></b>
                                </button>
                                <button type="button" class="text-green-800 bg-green-50 hover:bg-green-100 border border-green-800 text-xs font-poppins rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                                    <b>Dismiss</b>
                                </button>
                            </div>
                            <div class="mt-2 mb-4"></div>
                        </div>
                        @elseif($message = Session::get('error'))
                            <div class="flex p-4 mt-4 mb-2 text-sm font-poppins text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Danger</span>
                                <div>
                                    <span class="text-sm"><b>URL has failed to convert !</b></span>
                                    <br>
                                    <br>
                                    <span class="text-sm">Errors may occur that may come from this factor:</span>
                                    <ul class="mt-1.5 ml-4 text-xs list-disc list-inside">
                                        <li>Error due failure connection to API </li>
                                        <li>Error while uploading URL to the server</li>
                                        <li>Filename contain ambigous characters or symbols</li>
                                    </ul>
                                    <br>
                                    @error('error')
                                        <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
                                    @enderror
                                    <br>
                                    @error('uuid')
                                        <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
                                    @enderror
                                </div>
                            </div>
                        @elseif ($errors->any())
                            @error('error')
                            <div class="flex p-4 mt-4 mb-2 text-sm font-poppins text-red-800 border border-red-300 rounded-lg bg-red-50" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Danger</span>
                                <div>
                                    <span class="text-sm"><b>PDF has failed to convert !</b></span>
                                    <br>
                                    <span class="text-sm">Errors may occur that may come from this factor:</span>
                                    <ul class="mt-1.5 ml-4 text-xs list-disc list-inside">
                                        <li>Error due failure connection to API </li>
                                        <li>Error while uploading PDF to the server</li>
                                        <li>Filename contain ambigous characters or symbols</li>
                                    </ul>
                                    <br>
                                    @error('error')
                                        <span class="text-xs"><b>Error Reason: {{ $message }}</b></span>
                                    @enderror
                                    <br>
                                    @error('uuid')
                                        <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
                                    @enderror
                                    </div>
                            </div>
                            @enderror
                        @endif
                    </div>
                    <div>
                        <button type="submit" id="submitBtn" name="formAction" class="mx-auto mt-8 font-poppins font-semibold text-sky-400 border border-sky-400 rounded-lg cursor-pointer font-medium w-full h-10 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 hover:bg-sky-400 hover:text-white">Convert to PDF</button>
                    </div>
                </div>
            </div>
        </form>
        <script src="/ext-js/spinner.js"></script>
        @stop
    </div>
