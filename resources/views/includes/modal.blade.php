<div id="altLoadingModal" tabindex="-1" aria-hidden="true" class="fixed flex top-full w-full p-4 z-50 hidden overflow-x-hidden overflow-y-auto md:inset-0 h-full justify-center items-center pointer-events-none">
    <div class="relative w-full max-w-2xl max-h-full pointer-events-auto">
        <div class="relative bg-pc4 rounded-lg shadow">
            <div class="p-6 space-y-6 text-center">
                <p id="altTitleMessageModal" class="font-quicksand font-medium text-dt text-lg leading-relaxed">Processing PDF...</p>
                <div class="animate-spin-counter-clockwise inline-block w-12 h-12" role="status" aria-label="loading">
                    <img src="{{ asset('assets/icons/process.svg') }}" alt="loading">
                </div>
            </div>
        </div>
    </div>
</div>
<div id="loadingModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full justify-center items-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-pc4 rounded-lg shadow">
            <div class="p-6 space-y-6 text-center">
                <p id="titleMessageModal" class="font-quicksand font-medium text-dt text-lg leading-relaxed">Processing PDF...</p>
                <div class="animate-spin-counter-clockwise inline-block w-12 h-12" role="status" aria-label="loading">
                    <img src="{{ asset('assets/icons/process.svg') }}" alt="loading">
                </div>
            </div>
        </div>
    </div>
</div>
<div id="errModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full justify-center items-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-pc4 rounded-lg shadow">
            <div class="p-6 space-y-6">
                <img class="mx-auto mt-4 mb-4 text-dt3 w-12 h-12" src="{{ asset('assets/icons/exclamation.svg') }}" alt="exclamation">
                <p id="errMessageModal" class="font-quicksand font-semibold text-dt1 text-lg leading-relaxed text-center">....</p>
                <p id="errSubMessageModal" class="font-quicksand font text-dt3 text-xs leading-relaxed text-center">
                    <div id="altSubMessageModal" class="flex p-4 mb-4 text-sm text-lt1 bg-rt1 rounded-md" role="alert" style="display: none;">
                        <svg class="flex-shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                        </svg>
                        <span class="sr-only">Danger</span>
                        <div>
                            <span id="err-list-title" class="text-sm font-quicksand"></span>
                            <ul id="err-list"class="mt-1.5 list-disc list-inside font-quicksand font-semibold">
                                <li id="err-1"></li>
                            </ul>
                        </div>
                    </div>
                </p>
                <div class="flex flex-col items-center">
                    <button data-modal-hide="errModal" type="button" class="text-white bg-rt1 font-semibold font-quicksand rounded-md text-sm mx-auto px-5 py-2.5 w-2/6 lg:w-1/6" data-ripple-light="true">Ok</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="scsModalNotify" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full justify-center items-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-pc4 rounded-lg shadow">
            <div class="p-6 space-y-6 mx-auto">
                <img class="mx-auto mt-4 mb-4 text-dt3 w-12 h-12" src="{{ asset('assets/icons/information_scs.svg') }}" alt="information">
                <p id="scsMessageModalNotify" class="font-quicksand font-semibold text-dt1 text-lg leading-relaxed text-center">...</p>
                <div class="flex flex-col items-center">
                    <button data-modal-hide="scsModalNotify" id="scsModalBtn" type="button" class="text-white bg-ac mx-auto font-semibold font-quicksand rounded-md text-sm px-5 py-2.5 w-2/6 lg:w-1/6" data-ripple-light="true">Ok</button>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="versioningModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-xl max-h-full">
        <div class="relative bg-lt1 rounded-lg shadow">
            <div class="flex items-center justify-between bg-pc p-4 md:p-5 rounded-t">
                <h3 class="text-lg font-quicksand font-semibold text-lt1">
                    What's New
                </h3>
                <button type="button" class="text-lt1 bg-transparent hover:bg-lt1 hover:text-pc hover:bg-lt1 rounded-lg text-sm h-8 w-8 ms-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-toggle="versioningModal">
                    <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button data-ripple-light="true">
            </div>
            <div class="p-4 mx-4 mt-2 md:p-5">
                <h2 class="flex items-start mb-1 text-lg font-semibold font-quicksand text-pc" id="versioningTitle">Lorem</h2>
                <time class="block mb-3 text-sm font-normal leading-none font-quicksand text-dt3" id="versioningDate">Lorem</time>
                <ul class="font-quicksand font-semibold text-sm text-dt1 list-disc list-inside overflow-y-auto mx-2" id="versioningChangelog"></ul>
                <div class="flex flex-col items-center mt-5">
                    <button data-modal-hide="versioningModal" type="button" class="text-white bg-pc mx-auto font-semibold font-quicksand rounded-md text-sm px-5 py-2.5 w-4/6" data-ripple-light="true">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
