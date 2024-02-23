<div id="alert-scs" class="hidden p-4 mb-2 h-auto lg:h-6/6 bg-ac backdrop-filter backdrop-blur-md rounded-md bg-opacity-60" role="alert">
    <div class="flex items-center">
        <img class="flex-shrink-0 w-7 h-7 mr-2 -mt-1 text-pc4" src="{{ asset('assets/icons/Information.svg') }}" alt="information">
        <span class="sr-only">Info</span>
        <div id="scsMsgTitle" class="text-lg font-medium font-quicksand text-pc4">HANA PDF</div>
    </div>
    <div class="mt-2 mb-4 text-xs text-lt1 font-normal font-quicksand" id="scsMsgResult"></div>
    <div class="flex">
        <button type="button" class="border-2 border-lt1 text-lt1 font-normal font-quicksand text-sm rounded-lg px-3 py-1.5 mr-2 text-center inline-flex items-center">
            <img class="ml-0.5 mr-2 h-3 w-3 -mt-1" src="{{ asset('assets/icons/Download.svg') }}" alt="Downloads" />
            <b><a href="" id="scsMsgLink"></a></b>
        </button>
        <button type="button" class="bg-lt1 text-ac rounded-lg font-normal font-quicksand text-sm px-3 py-1.5 mr-2 text-center inline-flex items-center" data-dismiss-target="#alert-scs" aria-label="Close">
            <b>Dismiss</b>
        </button>
    </div>
</div>
<div id="alert-err" class="flex hidden p-4 mb-2 h-auto lg:h-36 bg-rt3 backdrop-filter backdrop-blur-md rounded-md bg-opacity-60" role="alert">
    <img class="w-7 h-7 mr-2 -mt-1 text-pc4" src="{{ asset('assets/icons/Information.svg') }}" alt="information">
    <span class="sr-only">Danger</span>
    <div>
        <span class="text-lg font-medium font-quicksand text-pc4" id="errMsgTitle"><b>HANA PDF</b></span>
        <br>
        <span class="text-xs text-lt1 font-normal font-quicksand"><b>Error Reason:</b> <span id="errMsg"></span></span>
        <br>
        <span class="text-xs text-lt1 font-normal font-quicksand"><b>Process ID:</b> <span id="errProcId"></span></span>
    </div>
</div>
