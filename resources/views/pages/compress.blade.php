<!doctype html> @extends('layouts.alternate-layout') @section('content')<div class="md:px-12 px-4"id=compress><section class="flex items-center flex-wrap justify-start lg:max-w-6xl max-w-lg sub-headline-viewport"><div class="mx-6 text-start"><div class="font-bold text-pc4 font-magistral lg:mb-8 lg:text-7xl mb-4 text-3xl">PDF Compress</div><div class="font-quicksand text-lt1 text-md font-light lg:text-3xl">Create smaller PDF size while trying to keep optimized for quality.</div></div></section><div class="flex flex-col p-2"id=dropzoneCmp><form action="{{ url('api/v1/file/upload') }}"class="flex items-center cursor-pointer justify-center backdrop-blur-md backdrop-filter bg-lt bg-opacity-15 dropzone flex-col h-fit lg:flex-row lg:h-72 lg:overflow-y-auto lg:w-4/6 max-h-full mb-2 min-h-96 mx-4 rounded-[40px] w-6/6 xl:flex-row"id=dropzoneArea method=post>{{ csrf_field() }}<div class="flex items-center flex-col justify-content p-4"id=dropzoneUiInit><img class="h-24 p-4 w-24"src="{{ asset('assets/icons/placeholder_pdf.svg') }}"><p class="font-quicksand mb-2 font-medium text-lt3 text-md">Drop PDF files here<p class="font-quicksand text-lt3 text-xs">Or</p><button class="flex items-center cursor-pointer justify-center mx-auto p-4 rounded-lg text-center bg-ac font-quicksand font-semibold h-12 mt-2 text-lt text-xs w-42"id=dropzoneUploadInit type=button><svg aria-hidden=true class="text-lt1 h-6 w-6"fill=currentColor viewBox="0 0 24 24"xmlns=http://www.w3.org/2000/svg><path clip-rule=evenodd d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z"fill-rule=evenodd></path></svg> <span class=ml-4>Choose File</span></button></div><div class="flex items-center flex-col justify-content border-2 border-dashed border-lt1 hidden order-1"id=dropzoneUiExt><button class="flex items-center cursor-pointer justify-center mx-auto p-4 rounded-lg text-center bg-transparent h-48 text-lt1 w-32"id=dropzoneUploadExt type=button><svg aria-hidden=true class="text-lt1 h-6 w-6"fill=currentColor viewBox="0 0 24 24"xmlns=http://www.w3.org/2000/svg><path clip-rule=evenodd d="M2 12a10 10 0 1 1 20 0 10 10 0 0 1-20 0Zm11-4.2a1 1 0 1 0-2 0V11H7.8a1 1 0 1 0 0 2H11v3.2a1 1 0 1 0 2 0V13h3.2a1 1 0 1 0 0-2H13V7.8Z"fill-rule=evenodd></path></svg></button></div></form><div class="flex flex-col mx-4 lg:w-3/6 mt-8"><label class="font-quicksand mb-2 block font-bold text-pc4 text-xl"for=firstRadio>Compression Quality</label><ul class="flex flex-col lg:flex-row xl:flex-row lg:mt-0 mb-4 mt-2"><li class="backdrop-blur-md backdrop-filter rounded-lg bg-opacity-50 bg-transparent border-2 border-lt mt-2 mx-2 p-2"id=firstCol><input id=firstInput value=comp style=display:none><div class=flex id=firstChk><div class="flex items-center h-5"><input id=firstRadio value=low aria-describedby=helper-firstRadioText class="border-ac focus:ring-0 h-4 hover:ring-2 mt-1.5 ring-0 ring-ac text-ac w-4 hover:ring-ac"name=compMethod type=radio></div><div class=ml-4><label class="font-quicksand text-lt1 font-semibold text-md"for=firstRadio id=firstRadioText>Lowest</label><p class="font-quicksand text-lt1 font-regular mt-1 text-sm"id=helper-firstRadioText>High quality, less compression</div></div><li class="backdrop-blur-md backdrop-filter rounded-lg bg-opacity-50 bg-transparent border-2 border-lt mt-2 mx-2 p-2"id=secondCol><input id=secondInput value=comp style=display:none><div class=flex id=secondChk><div class="flex items-center h-5"><input id=secondRadio value=recommended aria-describedby=helper-secondRadioText class="border-ac focus:ring-0 h-4 hover:ring-2 mt-1.5 ring-0 ring-ac text-ac w-4 hover:ring-pc2"name=compMethod type=radio></div><div class=ml-4><label class="font-quicksand text-lt1 font-semibold text-md"for=secondRadio id=secondRadioText>Recommended</label><p class="font-quicksand text-lt1 font-regular mt-1 text-sm"id=helper-firstRadioText>Good quality, good compression</div></div><li class="backdrop-blur-md backdrop-filter rounded-lg bg-opacity-50 bg-transparent border-2 border-lt mt-2 mx-2 p-2"id=thirdCol><input id=thirdInput value=comp style=display:none><div class=flex id=thirdChk><div class="flex items-center h-5"><input id=thirdRadio value=extreme aria-describedby=helper-thirdRadioText class="border-ac focus:ring-0 h-4 hover:ring-2 mt-1.5 ring-0 ring-ac text-ac w-4 hover:ring-pc2"name=compMethod type=radio></div><div class=ml-4><label class="font-quicksand text-lt1 font-semibold text-md"for=secondRadio id=thirdRadioText>High</label><p class="font-quicksand text-lt1 font-regular mt-1 text-sm"id=helper-firstRadioText>Less quality, high compression</div></div></ul><div dir=ltl><button class="font-quicksand text-lt1 font-semibold rounded-lg rounded-lg backdrop-blur-md backdrop-filter bg-opacity-50 bg-transparent border-2 border-lt cursor-pointer h-10 lg:w-4/6 mb-8 mt-6 mx-auto sm:mb-6 w-full"id=submitBtn type=submit data-ripple-light=true name=formAction>Compress PDF</button></div><div class="flex flex-col">@include('includes.alert')</div></div></div>@stop</div>
