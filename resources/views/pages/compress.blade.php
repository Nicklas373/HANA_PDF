@extends('layouts.default')
@section('content')
    <div class="bg-white bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern.svg')] dark:bg-slate-900 dark:bg-[url('https://flowbite.s3.amazonaws.com/docs/jumbotron/hero-pattern-dark.svg')]">
        <section>
            <div class="py-8 px-4 mt-4 mx-auto max-w-screen-xl text-center lg:py-16 z-0 relative">
                <h1 class="mb-4 text-4xl font-poppins font-semibold tracking-tight leading-none text-gray-900 md:text-5xl lg:text-6xl dark:text-white">PDF Compress</h1>
                <p class="mb-4 text-lg font-poppins font-normal text-gray-500 lg:text-xl sm:px-16 lg:px-48 dark:text-gray-200">Reduce PDF file size while try to keep optimize for maximal PDF quality</p>
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
        <form action="/compress/pdf" id="splitForm" method="POST" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="grid grid-columns-3 gap-4 p-4 mx-auto mb-8" id="grid-layout">
                <div id="pdfPreview" name="pdfPreview">
                    <br>
                    @if($message = Session::get('upload'))
                        <?php
                            $pdfFileName = basename($message, '.png');
                            $pdfAppend = "upload-pdf/".$pdfFileName.".pdf";
                            $pdfRealName = trim($pdfFileName, ".png").".pdf";
                            echo '<input type="text" id="fileAlt" name="fileAlt" class="" placeholder="" style="display: none;" value="'.$pdfAppend.'">
                                <div id="pdfImage" name="pdfImage" class="p-4 lg:p-2 w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 mx-auto bg-white border border-gray-200 rounded-lg shadow">
                                    <div class="text-left">
                                        <button type="button" class="text-white bg-slate-900 mr-2 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 hover:text-slate-900 hover:bg-slate-200" onClick="rotate()">
                                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"></path>
                                            </svg>
                                        </button>
                                        <a href="'.$pdfAppend.'" target="_blank">
                                            <button type="button" class="text-white mr-2 bg-slate-900 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 hover:text-slate-900 hover:bg-slate-200"">
                                                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                            </button>
                                        </a>
                                        <button type="button" class="text-white bg-slate-900 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 hover:text-slate-900 hover:bg-slate-200" onClick="remove()">
                                            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="pdfImageCaption" class="p-4 lg:p-2 mb-6 w-fit mx-auto hover:transition hover:ease-in-out hover:delay-150 hover:scale-105 hover:transform-gpu hover:duration-300">
                                        <img class="max-h-[50%] max-w-[50%] rounded-lg mx-auto" src="'.$message.'" alt="image description">
                                    </div>
                                    <div class="text-center">
                                        <span id="caption" class="mt-2 mx-auto text-sm text-center text-slate-900 font-semibold" value="{{ $message }}" >'.$pdfRealName.'</span>
                                    </div>
                                </div>
                            ';
                        ?>
                    @endif
                </div>
                <div class="p-2 w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 mx-auto bg-white border border-gray-200 rounded-lg shadow">
                    <label class="block mb-2 font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="file_input">Upload PDF file</label>
                    <input class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400"aria-describedby="file_input_help" id="file_input" name="file" type="file" accept="application/pdf" onClick="changeButtonColor()">
                    <p class="mt-1 font-poppins text-sm text-gray-500 dark:text-gray-300" id="file_input_help">PDF (Max. 25 MB)</p>
                    @if ($message = Session::get('success'))
                        <div class="flex p-4 mt-2 mb-2 text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div class="ml-3 text-sm font-medium">
                                Compress PDF Success !, Click <a href="{{ $message }}" class="font-poppins font-semibold underline hover:no-underline">Here </a>to download.
                            </div>
                        </div>
                    @elseif($message = Session::get('error'))
                        <div class="flex p-4 mt-2 mb-2 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                            <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                            <span class="sr-only">Info</span>
                            <div>
                                <span class="font-poppins font-medium">Upload PDF Error ! </span> {{ $message }}
                            </div>
                        </div>
                    @elseif ($errors->any())
                        @error('error')
                            <div class="flex p-4 mt-2 mb-2 text-sm text-red-800 border border-red-300 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 dark:border-red-800" role="alert">
                                <svg aria-hidden="true" class="flex-shrink-0 inline w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>
                                <span class="sr-only">Info</span>
                                <div>
                                    <span class="font-poppins font-medium">Upload PDF Error ! </span> {{ $message }}
                                </div>
                            </div>
                        @enderror
                    @endif
                </div>
                <button type="submit" id="submitBtn" name="formAction" data-modal-target="loadingModal" data-modal-toggle="loadingModal" class="mb-2 mx-auto font-poppins text-slate-900 bg-slate-200 rounded-lg cursor-pointer font-medium w-full h-16 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 font-semibold" onClick="onClick()" value="upload">Upload PDF</button>
                <div id="pdfCompLayout" class="p-4 lg:p-2 w-full sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 mx-auto bg-white border border-gray-200 rounded-lg shadow" style="display: none;">
                    <label class="block mb-2 font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="file_input">Compression Quality</label>
                    <ul class="grid w-full gap-4 lg:grid-cols-1 2xl:grid-cols-3 mt-4 mb-4">
                        <li>
                            <input type="radio" id="comp-low" name="compMethod" value="low" class="hidden peer">
                            <label for="comp-low" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">
                                <div class="block">
                                    <div class="w-full text-lg font-semibold">Lowest</div>
                                    <div class="w-full">High quality, less compression</div>
                                </div>
                                <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </label>
                        </li>
                        <li>
                            <input type="radio" id="comp-rec" name="compMethod" value="recommended" class="hidden peer">
                            <label for="comp-rec" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">
                                <div class="block">
                                    <div class="w-full text-lg font-semibold">Recommended</div>
                                    <div class="w-full">Good quality, good compression</div>
                                </div>
                                <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </label>
                        </li>
                        <li>
                            <input type="radio" id="comp-high" name="compMethod" value="extreme" class="hidden peer">
                            <label for="comp-high" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">
                                <div class="block">
                                    <div class="w-full text-lg font-semibold">High</div>
                                    <div class="w-full">Less quality, high compression</div>
                                </div>
                                <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                            </label>
                        </li>
                    </ul>
                </div>
                <button type="submit" id="submitBtn_1" name="formAction" data-modal-target="loadingModal" data-modal-toggle="loadingModal" class="mb-2 mx-auto font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium font-semibold w-full h-16 sm:w-5/5 md:w-4/5 lg:w-3/5 xl:w-2/5 text-center" onClick="onClick()" value="compress" style="display: none;">Compress PDF</button>
            </div>
        </form>
        <script src="/ext-js/compress.js"></script>
        <script src="/ext-js/spinner.js"></script>
    @stop
    </div>
