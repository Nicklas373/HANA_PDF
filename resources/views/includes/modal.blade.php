<div id="loadingModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full justify-center items-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="p-6 space-y-6 text-center">
                <p id="titleMessageModal" class="font-poppins font-semibold text-slate-700 text-base leading-relaxed"></p>
                <div class="animate-spin inline-block w-12 h-12 border-[4.5px] border-current border-t-transparent text-sky-400 rounded-full" role="status" aria-label="loading">
                    <span class="sr-only">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="errModal" data-modal-backdrop="static" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full justify-center items-center">
    <div class="relative w-full max-w-2xl max-h-full">
        <div class="relative bg-white rounded-lg shadow">
            <div class="p-6 space-y-6">
                <svg class="mx-auto mt-4 mb-4 text-gray-400 w-12 h-12 dark:text-gray-200" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 11V6m0 8h.01M19 10a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <p id="errMessageModal" class="font-poppins font-semibold text-gray-900 text-lg leading-relaxed text-center">....</p>
                <p id="errSubMessageModal" class="font-poppins font text-slate-500 text-xs leading-relaxed text-center">
                    <div id="altSubMessageModal" class="flex p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert" style="display: none;">
                        <svg class="flex-shrink-0 inline w-4 h-4 me-3 mt-[2px]" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"></path>
                        </svg>
                        <span class="sr-only">Danger</span>
                        <div>
                            <span id="err-list-title" class="text-sm font-poppins"></span>
                            <ul id="err-list"class="mt-1.5 list-disc list-inside font-bold">
                                <li id="err-1"></li>
                            </ul>
                        </div>
                    </div>
                </p>
                <div class="flex flex-col items-center">
                    <button data-modal-hide="errModal" type="button" class="text-white bg-red-600 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 dark:focus:ring-red-800 font-medium rounded-lg text-sm mx-auto px-5 py-2.5">OK</button>
                </div>
            </div>
        </div>
    </div>
</div>
