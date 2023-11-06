<nav class="w-full fixed top-0 left-0 backdrop-filter backdrop-blur-md bg-opacity-50 bg-slate-300 z-10">
  <div class="flex max-w-full flex-wrap items-center justify-between p-4">
    <a href="/" class="md:mb-2 px-2 flex items-center md:mb-0">
      <span class="font-poppins h-8 self-center text-2xl font-semibold text-sky-400">HANA</span>&nbsp;
      <span class="font-poppins mr-14 self-center text-2xl font-semibold text-slate-700">PDF</span>
    </a>
    <div class="flex mt-2 md:order-2 md:mt-0 lg:order-0">
      <button type="button" data-modal-target="defaultModal" data-modal-toggle="defaultModal" class="mr-3 rounded-lg bg-sky-700 px-4 py-2 text-center text-sm font-medium font-poppins text-white hover:bg-sky-400 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800 md:mr-0">Get Started</button>
      <button data-collapse-toggle="navbar-cta" type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-lg p-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 md:hidden" aria-controls="navbar-cta" aria-expanded="false">
        <span class="sr-only">Open main menu</span>
        <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
        </svg>
      </button>
    </div>
    <div class="mt-2 hidden w-full items-center justify-between md:order-1 md:flex md:w-auto md:flex-1 md:mt-0 md:mb-2" id="navbar-cta">
      <ul class="mt-4 flex flex-col rounded-lg md:px-4 font-medium md:mt-0 md:flex-row md:space-x-8 md:border-0 md:p-0">
        <li>
          <a href="/compress" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500 md:p-0" aria-current="page">Compress</a>
        </li>
        <li>
            <button id="dropdownNavbarLink" data-dropdown-toggle="dropdownNavbar" data-dropdown-placement="bottom" value="0" class="flex items-center justify-between w-full font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500 md:p-0" onClick="dropdownManage()">Convert
                <svg id="dropdownNavbarImage" class="w-2.5 h-2.5 ml-2.5 rotate-[-90deg] transform duration-500 ease-in-out transform-gpu" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4"/>
                </svg>
            </button>
            <!-- Dropdown menu -->
            <div id="dropdownNavbar" value="0" class="hidden w-auto md:w-32 h-auto font-normal bg-slate-300 rounded-lg shadow z-10 animate-fade duration-500 transform-gpu block inset-x-5">
                <ul class="py-2 text-sm text-gray-700">
                    <li>
                        <a href="/cnvToPDF">
                            <button id="cnvToPDFdropdown" type="button" class="flex items-center justify-between w-full font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500" onClick="dropdownCnvToPDF()">To PDF</button>
                        </a>
                    </li>
                    <li>
                        <a href="/cnvFromPDF">
                            <button id="cnvFromPDFdropdown" type="button" class="flex items-center justify-between w-full font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500" onClick="dropdownCnvFromPDF()">From PDF</button>
                        </a>
                    </li>
                    <li>
                        <a href="/htmltopdf">
                            <button id="cnvFromPDFdropdown" type="button" class="flex items-center justify-between w-full font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500" onClick="dropdownCnvFromPDF()">From HTML</button>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li>
          <a href="/merge" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500 md:p-0" aria-current="page">Merge</a>
        </li>
        <li>
          <a href="/split" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500 md:p-0" aria-current="page">Split</a>
        </li>
        <li>
          <a href="/watermark" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-slate-500 md:p-0" aria-current="page">Watermark</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<div id="defaultModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
    <div class="relative max-h-full w-full max-w-2xl">
        <!-- Modal content -->
        <div class="relative rounded-lg bg-slate-200 shadow">
            <!-- Modal header -->
            <div class="flex items-start justify-between rounded-t border-b bg-slate-900 p-4 hover:text-sky-400">
            <h3 class="font-poppins text-xl text-center font-semibold text-slate-200">Welcome to HANA PDF</h3>
            <button type="button" class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-sm text-slate-900 hover:text-blue-600" data-modal-hide="defaultModal">
                <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            </div>
            <!-- Modal body -->
            <div class="space-y-4 p-4">
            <p class="font-poppins text-base leading-relaxed text-slate-900"><b>HANA PDF</b> A web-based application that has feature to manage PDF files easily and quicky. It has features to compress, convert, merge, split, watermarks and more (soon).</p>
            <p class="font-poppins text-base leading-relaxed text-slate-900">Empowered by using iLovePDF and Aspose Cloud SDK to support much feature for PDF management tools, visit more on our main website !</p>
            </div>
            <!-- Modal footer -->
            <div class="flex items-center space-x-2 rounded-b border-t border-gray-200 p-4">
            <button data-modal-hide="defaultModal" type="button" class="rounded-lg bg-slate-900 px-5 py-2.5 text-center text-sm font-poppins text-white hover:bg-gray-300 hover:text-slate-900 focus:outline-none focus:ring-4"><a href="https://hana-ci.com" target="_blank">Visit Main Website</a></button>
            <button data-modal-hide="defaultModal" type="button" class="rounded-lg bg-slate-900 px-5 py-2.5 text-center text-sm font-poppins text-white hover:bg-gray-300 hover:text-slate-900 focus:outline-none focus:ring-4"><a href="guide/manual_book_20230903_rev1.pdf">Manual Book</a></button>
            </div>
        </div>
    </div>
</div>
