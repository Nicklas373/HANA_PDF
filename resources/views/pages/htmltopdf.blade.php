@extends('layouts.default')
@section('content')
    <div>
        <section>
            <div class="py-8 px-4 mt-16 md:mt-24 lg:mt-16 mx-auto max-w-screen-xl text-center lg:py-16 z-0 relative">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">HTML To PDF</h1>
                <p class="mb-4 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Convert URL address or web page into PDF format</p>
            </div>
        </section>
        <div id="loadingModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative w-full max-w-2xl max-h-full">
                <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
                    <div class="p-6 space-y-6">
                        <p class="font-poppins font-semibold text-gray-900 text-base leading-relaxed text-center">
                            Processing PDF...
                        </p>
                        <svg aria-hidden="true" class="w-16 h-16 text-gray-200 animate-spin fill-blue-600 mx-auto" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/><path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/></svg>
                    </div>
                </div>
            </div>
        </div>
        <form action="/htmltopdf/web" id="splitForm" method="POST" enctype="multipart/form-data">
        {{ csrf_field() }}
            <div class="grid grid-rows-1 p-4 mb-8" id="grid-layout">
                <div class="p-2 mb-4 w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 mx-auto bg-white border border-gray-200 rounded-lg shadow">
                    <label for="urlToPDF" class="block mb-2 font-poppins text-sm font-semibold text-gray-900 dark:text-white">Write the Website URL</label>
                    <input type="text" id="urlToPDF" name="urlToPDF" class="mb-2 bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="https://hana-ci.com" required>
                    @if ($message = Session::get('stats'))
                        <div id="alert-additional-content-3" class="p-4 mt-2 mb-2 text-slate-900 border border-slate-900 rounded-lg bg-gray-50" role="alert">
                            <div class="flex items-center mb-2">
                                <svg class="flex-shrink-0 w-4 h-4 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Info</span>
                                <h3 class="text-md font-poppins">PDF has successfully converted !</h3>
                            </div>
                            <div class="flex">
                                <button type="button" class="text-white bg-slate-900 hover:bg-gray-600 focus:ring-4 focus:outline-none focus:ring-slate-900 font-medium font-poppins rounded-lg text-xs px-3 py-1.5 mr-2 text-center inline-flex items-center">
                                    <svg class="ml-0.5 mr-2 h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"> <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path> <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path> </svg>
                                    <b><a href="{{ session('res') }}">Download File</a></b>
                                </button>
                                <button type="button" class="text-slate-900 bg-transparent border border-slate-900 hover:bg-gray-900 hover:text-white focus:ring-4 focus:outline-none focus:ring-slate-900 font-medium font-poppins rounded-lg text-xs px-3 py-1.5 mr-2 text-center inline-flex items-center" data-dismiss-target="#alert-additional-content-3" aria-label="Close">
                                    <b>Dismiss</b>
                                </button>
                            </div>
                        </div>
                    @elseif($message = Session::get('error'))
                        <div class="flex p-4 mt-2 mb-2 text-sm font-poppins text-red-800 rounded-lg bg-red-50" role="alert">
                            <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                            </svg>
                            <span class="sr-only">Danger</span>
                            <div>
                                <span class="font-medium"><b>PDF has failed to convert !</b></span>
                                <br>
                                <br>
                                <span class="font-medium">Errors may occur that may come from this factor:</span>
                                <ul class="mt-1.5 ml-4 list-disc list-inside">
                                    <li>Error due failure connection to API </li>
                                    <li>Error while uploading PDF to the server</li>
                                    <li>Filename contain ambigous characters or symbols</li>
                                </ul>
                                <br>
                                @error('error')
                                <span class="text-xs"><b>Error Log: {{ $message }}</b></span>
                                @enderror
                                <br>
                                @error('uuid')
                                    <span class="text-xs"><b>Process ID: {{ $message }}</b></span>
                                @enderror
                            </div>
                        </div>
                    @elseif ($errors->any())
                        @error('error')
                            <div class="flex p-4 mt-2 mb-2 text-sm font-poppins text-red-800 rounded-lg bg-red-50" role="alert">
                                <svg class="flex-shrink-0 inline w-4 h-4 mr-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                                </svg>
                                <span class="sr-only">Danger</span>
                                <div>
                                    <span class="font-medium"><b>PDF has failed to convert !</b></span>
                                    <br>
                                    <span class="font-medium">Errors may occur that may come from this factor:</span>
                                    <ul class="mt-1.5 ml-4 list-disc list-inside">
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
                <button type="submit" id="submitBtn" data-modal-target="loadingModal" data-modal-toggle="loadingModal" class="mb-6 sm:mb-2 mx-auto font-poppins text-slate-900 bg-slate-200 rounded-lg cursor-pointer font-medium w-full h-16 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 font-semibold" onClick="onClick()">Convert to PDF</button>
            </div>
        </form>
        <script src="/ext-js/spinner.js"></script>
        @stop
    </div>
