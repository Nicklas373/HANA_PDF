<div id="previewModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 hidden left-0 right-0 z-50 w-full p-4 overflow-x-hidden overflow-y-auto h-full justify-center items-center flex">
    <div class="relative max-w-6xl w-full h-full">
        <div class="flex items-end py-4 justify-end border-b rounded-t">
            <button type="button" class="text-lt1 bg-transparent hover:bg-lt1 hover:text-ac rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="previewModal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
        </div>
        <div class="flex w-full h-5/6">
            <div class="w-full h-full">
                <div id="adobe-dc-view" class="w-full h-full">
                    <script src="https://acrobatservices.adobe.com/view-sdk/viewer.js"></script>
                    <script type="text/javascript" id="adobe_preview"></script>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="previewDocumentModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 hidden left-0 right-0 z-50 w-full p-4 overflow-x-hidden overflow-y-auto h-full justify-center items-center flex">
    <div class="relative max-w-6xl w-full h-full">
        <div class="flex items-end py-4 justify-end border-b rounded-t">
            <button type="button" class="text-lt1 bg-transparent hover:bg-lt1 hover:text-ac rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="previewDocumentModal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
        </div>
        <div class="flex w-full h-5/6">
            <div class="w-full h-full">
                <div id="iFrameBorder" class="w-full h-full bg-lt1 backdrop-filter backdrop-blur-md bg-opacity-50">
                    <div class="animate-spin-counter-clockwise w-16 h-16 mt-64 absolute top-0 bottom-0 left-0 right-0 mx-auto" role="status" aria-label="loading">
                        <img src="{{ asset('assets/icons/Process.svg') }}" alt="loading">
                    </div>
                    <p class="font-quicksand font-semibold text-pc4 text-semibold leading-relaxed text-center mt-48 absolute top-0 bottom-0 left-0 right-0">
                        Loading document preview...
                    </p>
                </div>
                <iframe id="iFrame" src="" class="w-full h-full"></iframe>
            </div>
        </div>
    </div>
</div>
<div id="previewImgModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 hidden left-0 right-0 z-50 w-full p-4 overflow-x-hidden overflow-y-auto h-full justify-center items-center flex">
    <div class="relative max-w-6xl w-full h-full">
        <div class="flex items-end py-4 justify-end border-b rounded-t">
            <button type="button" class="text-lt1 bg-transparent hover:bg-lt1 hover:text-ac rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center" data-modal-hide="previewImgModal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
        </div>
        <div class="flex w-full h-5/6">
            <div class="w-full h-full">
                <img id="imgPrv" src="" class="w-full h-full object-scale-down"/>
            </div>
        </div>
    </div>
</div>
