function changeButtonColor() {
    document.getElementById('file_input').addEventListener('change', function(e) {
        var fullPath = document.getElementById('file_input').value;
        if (fullPath) {
            var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
            var filename = fullPath.substring(startIndex);
            if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                filename = filename.substring(1);
            }
            if (filename == "") {
                document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
                document.getElementById('submitBtn').style.color="#0f172a"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#0f172a"
                document.getElementById('submitBtn').style.color="#ffffff"
            }
        }
    });
}

function splitLayout2_split(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="mt-8 mb-4" id="splitLayout2">
            <div>
                <h3 class="block mb-4 font-poppins text-sm font-medium text-slate-900 dark:text-white">Range mode:</h3>
                <div class="grid md:grid-cols-1 lg:grid-cols-3 gap-4 mt-4" role="group">
                    <button type="button" class="px-4 py-2 me-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_wthn()">
                        Within ranges
                    </button>
                    <button type="button" class="px-4 py-2 me-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_range()">
                        Fixed ranges
                    </button>
                    <button type="button" class="px-4 py-2 me-2 font-poppins font-medium text-slate-200 bg-slate-900 rounded-lg border border-blue-700 hover:bg-slate-200 hover:text-blue-700 focus:z-10 focus:ring-2 focus:bg-slate-200 focus:ring-slate-900 focus:text-slate-900" onClick="splitLayout3_cstm()">
                        Custom ranges
                    </button>
                </div>
            </div>
            <div id="splitLayout3"></div>
        </div>
    `;
};

function splitLayout2_extract(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="mt-0"></div>
    `;
    document.getElementById("submitBtn_2").style.display= "none";
    document.getElementById("submitBtn_3").style.display= null;
};

function splitLayout3_cstm(){
    showLayout3();
    document.getElementById("splitLayout3").innerHTML = `
        <div class="mt-8">
            <div class="grid gap-2 mb-4 md:grid-cols-1">
                <div>
                    <label for="customPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Pages to extract:</label>
                    <input type="text" id="customPage" name="customPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1,5-8">
                </div>
                <div class="flex items-center mt-2 mb-4">
                    <input id="mergePDF" name="mergePDF" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label for="mergePDF" class="ml-2 text-sm font-poppins font-medium text-gray-900 dark:text-gray-300">Merge all ranges in one PDF file.</label>
                </div>    
            </div>
        </div>
    `;
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function splitLayout3_wthn(){
    showLayout3();
    document.getElementById("splitLayout3").innerHTML = `
        <div class="mt-8">
            <div class="grid gap-6 mb-4 md:grid-cols-2">
                <div>
                    <label for="fromPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">From page:</label>
                    <input type="number" id="fromPage" name="fromPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1">
                </div>
                <div>
                    <label for="toPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">To:</label>
                    <input type="number" id="toPage" name="toPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="10">
                </div>
            </div>
            <div class="flex items-center mt-2 mb-4">
                <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                <label for="mergePDF" class="ml-2 text-sm font-poppins font-medium text-gray-900 dark:text-gray-300">Merge all ranges in one PDF file.</label>
            </div>               
        </div>
    `;
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function splitLayout3_range(){
    document.getElementById("splitLayout3").innerHTML = `
    <div class="mt-8">
        <div class="grid gap-6 mb-4 md:grid-cols-2">
            <div>
                <label for="fixedPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Split in page ranges of:</label>
                <input type="number" id="page_range" name="fixedPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1-6">
            </div>
        </div>         
    </div>
    `;
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function showLayout3() {
    var layout = document.getElementById('splitLayout3');
    layout.style = null
}

function remove_split() {
    var pdfCompBtn = document.getElementById('submitBtn_1');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfSplit1 = document.getElementById("splitLayout1");
    var pdfSplit2 = document.getElementById("splitLayout2");
    if (pdfCompBtn !== null) {
        pdfCompBtn.style.display="none";
    };
    pdfImage.style.display="none";
    pdfSplit1.style.display="none";
    pdfSplit2.style.display="none";
}

function init() {
    var fullPath = document.getElementById('caption').value;
    var splitLayout = document.getElementById('splitLayout1')
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#0f172a"
        splitLayout.style = null
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#0f172a"
        document.getElementById('submitBtn').style.color="#ffffff"
        splitLayout.style.display="none"
    }
}

init();