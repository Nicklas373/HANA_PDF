function splitLayout2_split(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="p-4 lg:p-2 w-full md:w-2/5 h-fit mx-auto mt-4 mb-8 lg:mb-8 xl:mb-0 bg-white border border-gray-200 rounded-lg shadow" id="splitLayout2">
            <div class="p-2 mx-auto">
                <h3 class="font-poppins font-medium text-gray-900 dark:text-white">Range mode :</h3>
                    <div class="grid sm:grid-cols-1 lg:grid-cols-3 gap-6 mt-4" role="group">
                        <button type="button" class="px-4 py-2 me-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_wthn()">
                            Within ranges
                        </button>
                        <button type="button" class="px-4 py-2 me-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_range()">
                            Fixed ranges
                        </button>
                        <button type="button" class="px-4 py-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_cstm()">
                            Custom ranges
                        </button>
                    </div>
                </div>
                <div class="mt-8" id="splitLayout3"></div>
            </div>
        </div>
    `;
};

function splitLayout2_extract(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="p-2 mx-auto mb-4 xl:mb-0">
            <button type="submit" id="submitBtn" class="block mx-auto mt-8 font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium w-full h-16 md:w-2/5 md:h-16 lg:w-1/5 lg:h-1/5 p-4 text-center" onClick="onClick()">Extract PDF</button>
        </div>
    `;
    document.getElementById("splitForm").action = "/extract/pdf";
};

function splitLayout3_cstm(){
    document.getElementById("splitLayout3").innerHTML = `
        <div class="mt-8" id="splitLayout3">
            <div class="grid gap-6 mb-4 md:grid-cols-1">
                <div>
                    <label for="customPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Pages to extract:</label>
                    <input type="text" id="customPage" name="customPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="example 1,5-8" required>
                </div>
                <div class="flex items-center mt-2">
                    <input id="mergePDF" name="mergePDF" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="mergePDF" class="ml-2 text-sm font-poppins font-medium text-gray-900 dark:text-gray-300">Merge all ranges in one PDF file.</label>
                </div>    
            </div>
        </div>
        <button type="submit" id="submitBtn" class="block mx-auto font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium w-full h-16 md:w-2/5 md:h-16 lg:w-3/5 lg:h-1/5 xl:w-1/5 xl:h-1/5 p-4 text-center" onClick="onClick()">Split PDF</button>
    `;
    document.getElementById("splitForm").action = "/split/pdf";;
};

function splitLayout3_wthn(){
    document.getElementById("splitLayout3").innerHTML = `
    <div class="mt-8 mb-4 md:mb-0" id="splitLayout3">
        <div class="grid gap-6 mb-4 md:grid-cols-2">
            <div>
                <label for="fromPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">From page</label>
                <input type="number" id="fromPage" name="fromPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1" required>
            </div>
            <div>
                <label for="toPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">To</label>
                <input type="number" id="toPage" name="toPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="10" required>
            </div>
        </div>
        <div class="flex items-center mt-4 mb-4">
            <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            <label for="mergePDF" class="ml-2 text-sm font-poppins font-medium text-gray-900 dark:text-gray-300">Merge all ranges in one PDF file.</label>
        </div>               
    </div>
    <button type="submit" id="submitBtn" class="block mx-auto font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium w-full h-16 md:w-2/5 md:h-16 lg:w-3/5 lg:h-1/5 xl:w-1/5 xl:h-1/5 p-4 text-center" onClick="onClick()">Split PDF</button>
    `;
    document.getElementById("splitForm").action = "/split/pdf";
};

function splitLayout3_range(){
    document.getElementById("splitLayout3").innerHTML = `
    <div class="mt-8" id="splitLayout3">
        <div class="grid gap-6 mb-4 md:grid-cols-2">
            <div>
                <label for="fixedPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Split in page ranges of</label>
                <input type="number" id="page_range" name="fixedPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1" required>
            </div>
        </div>         
    </div>
    <button type="submit" id="submitBtn" class="block mx-auto font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium w-full h-16 md:w-2/5 md:h-16 lg:w-3/5 lg:h-1/5 xl:w-1/5 xl:h-1/5 p-4 text-center" onClick="onClick()">Split PDF</button>
    `;
    document.getElementById("splitForm").action = "/split/pdf";
};