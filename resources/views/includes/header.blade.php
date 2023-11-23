<nav class="fixed left-0 top-0 z-10 lg:h-16 w-full bg-slate-300 bg-opacity-50 backdrop-blur-md backdrop-filter">
    <div class="flex max-w-full flex-wrap items-center justify-between p-4">
      <a href="/" class="flex items-center px-2 md:mb-2">
        <span class="font-poppins h-8 self-center text-2xl font-semibold text-sky-400">HANA</span>&nbsp;
        <span class="font-poppins mr-14 self-center text-2xl font-semibold text-slate-700">PDF</span>
      </a>
      <div class="lg:order-0 mt-2 flex md:order-2 md:mt-0">
        <button type="button" class="font-poppins mb-2 lg:-mt-2.5 lg:mb-0 mr-3 rounded-lg bg-sky-700 px-4 py-2 text-center text-sm text-white hover:bg-sky-400 focus:outline-none focus:ring-4 focus:ring-blue-300 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Get Started</button>
        <button data-collapse-toggle="navbar-cta" type="button" class="inline-flex mb-2 lg:-mt-1 lg:mb-0 h-10 w-10 items-center justify-center rounded-lg p-2 text-sm text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600 md:hidden" aria-controls="navbar-cta" aria-expanded="false">
          <span class="sr-only">Open main menu</span>
          <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 17 14">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M1 1h15M1 7h15M1 13h15" />
          </svg>
        </button>
      </div>
      <div class="mt-2 hidden w-full items-center justify-between md:order-1 md:mb-2 md:mt-0 md:flex md:w-auto md:flex-1" id="navbar-cta">
        <ul class="mt-4 flex flex-col rounded-lg font-medium md:mt-0 md:flex-row md:space-x-8 md:border-0 md:p-0 md:px-4">
          <li>
            <a href="/compress" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400 md:p-0" aria-current="page" type="button" data-ripple-light="true">Compress</a>
          </li>
          <li>
            <button id="dropdownNavbarLink" data-dropdown-toggle="dropdownNavbar" data-dropdown-placement="bottom" value="0" class="font-poppins flex w-full items-center justify-between rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400 md:p-0" onClick="dropdownManage()" type="button" data-ripple-light="true">
              Convert
              <svg id="dropdownNavbarImage" class="ml-2.5 h-2.5 w-2.5 rotate-[-90deg] transform-gpu duration-500 ease-in-out" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 10 6">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 4 4 4-4" />
              </svg>
            </button>
            <div id="dropdownNavbar" value="0" class="animate-fade inset-x-5 z-10 hidden h-auto w-auto transform-gpu rounded-lg bg-slate-200 font-normal shadow duration-500 md:w-32">
              <ul class="py-2 text-sm text-gray-700">
                <li>
                  <a href="/cnvToPDF">
                    <button id="cnvToPDFdropdown" type="button" class="font-poppins flex w-full items-center justify-between rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400" onClick="dropdownCnvToPDF()" type="button" data-ripple-light="true">To PDF</button>
                  </a>
                </li>
                <li>
                  <a href="/cnvFromPDF">
                    <button id="cnvFromPDFdropdown" type="button" class="font-poppins flex w-full items-center justify-between rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400" onClick="dropdownCnvFromPDF()" type="button" data-ripple-light="true">From PDF</button>
                  </a>
                </li>
                <li>
                  <a href="/htmltopdf">
                    <button id="cnvFromPDFdropdown" type="button" class="font-poppins flex w-full items-center justify-between rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400" onClick="dropdownCnvFromPDF()" type="button" data-ripple-light="true">From HTML</button>
                  </a>
                </li>
              </ul>
            </div>
          </li>
          <li>
            <a href="/merge" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400 md:p-0" aria-current="page" type="button" data-ripple-light="true">Merge</a>
          </li>
          <li>
            <a href="/split" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400 md:p-0" aria-current="page" type="button" data-ripple-light="true">Split</a>
          </li>
          <li>
            <a href="/watermark" class="font-poppins block rounded py-2 pl-3 pr-4 font-semibold text-slate-700 hover:text-sky-400 md:p-0" aria-current="page" type="button" data-ripple-light="true">Watermark</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
  <!-- Disable for temporary
  <div id="defaultModal" tabindex="-1" aria-hidden="true" class="fixed left-0 right-0 top-0 z-50 hidden h-[calc(100%-1rem)] max-h-full w-full overflow-y-auto overflow-x-hidden p-4 md:inset-0">
      <div class="relative max-h-full w-full max-w-2xl">
          <div class="relative rounded-lg bg-slate-200 shadow">
              <div class="flex items-start justify-between rounded-t border-b bg-slate-900 p-4 hover:text-sky-400">
              <h3 class="font-poppins text-xl text-center font-semibold text-slate-200">Welcome to HANA PDF</h3>
              <button type="button" class="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-lg bg-slate-200 text-sm text-slate-900 hover:text-blue-600" data-modal-hide="defaultModal">
                  <svg class="h-3 w-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"></path>
                  </svg>
                  <span class="sr-only">Close modal</span>
              </button>
              </div>
              <div class="space-y-4 p-4">
              <p class="font-poppins text-base leading-relaxed text-slate-900"><b>HANA PDF</b> A web-based application that has feature to manage PDF files easily and quicky. It has features to compress, convert, merge, split, watermarks and more (soon).</p>
              <p class="font-poppins text-base leading-relaxed text-slate-900">Empowered by using iLovePDF and Aspose Cloud SDK to support much feature for PDF management tools, visit more on our main website !</p>
              </div>
              <div class="flex items-center space-x-2 rounded-b border-t border-gray-200 p-4">
              <button data-modal-hide="defaultModal" type="button" class="rounded-lg bg-slate-900 px-5 py-2.5 text-center text-sm font-poppins text-white hover:bg-gray-300 hover:text-slate-900 focus:outline-none focus:ring-4"><a href="https://hana-ci.com" target="_blank">Visit Main Website</a></button>
              <button data-modal-hide="defaultModal" type="button" class="rounded-lg bg-slate-900 px-5 py-2.5 text-center text-sm font-poppins text-white hover:bg-gray-300 hover:text-slate-900 focus:outline-none focus:ring-4"><a href="guide/manual_book_20230903_rev1.pdf">Manual Book</a></button>
              </div>
          </div>
      </div>
  </div>
  -->
