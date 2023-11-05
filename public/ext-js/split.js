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
                document.getElementById('submitBtn').style.color="#38bdf8"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="#e2e8f0"
            }
        }
    });
}

function LowChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt").style.color = '#38bdf8'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
}

function LowChkSplitClick2() {
    document.getElementById("lowestChk2").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt2").style.color = '#38bdf8'
    document.getElementById("recChk2").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt2").style.color = '#1e293b'
}

function RecChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt").style.color = '#38bdf8'
}

function RecChkSplitClick2() {
    document.getElementById("lowestChk2").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt2").style.color = '#1e293b'
    document.getElementById("recChk2").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt2").style.color = '#38bdf8'
}

function splitLayout2_split(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="mt-6 mb-4" id="splitLayout2">
            <div>
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Split Options</label>
                <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4 mt-4 mb-4">
                    <li id="lowestChk2" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="" name="SplitOpt2" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="splitLayout3_wthn()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-slate-800 font-poppins" id="lowest-txt2">Selected Pages</label>
                            </div>
                        </div>
                    </li>
                    <li id="recChk2" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="" name="SplitOpt2" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="splitLayout3_cstm()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-slate-800 font-poppins" id="rec-txt2">Custom Pages</label>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div id="splitLayout3"></div>
    `;
    LowChkSplitClick();
    document.getElementById("submitBtn_2").style.display= "none";
    document.getElementById("submitBtn_3").style.display= "none";
};

function splitLayout2_extract(){
    document.getElementById("splitLayout2").innerHTML = `
        <div class="mt-6">
            <div class="grid gap-2 mb-4 md:grid-cols-1">
                <div>
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Custom Pages</label>
                    <input type="text" id="customPage" name="customPage" class="mt-4 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-4/6 p-2.5" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all">
                </div>
                <div class="flex items-center mt-2">
                <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="w-4 h-4 text-sky-400 bg-gray-300 border-sky-400 rounded focus:ring-sky-400 focus:ring-2">
                    <label for="mergePDF" class="ml-2 text-xs font-poppins text-gray-900">Merge all pages into one PDF file.</label>
                </div>
            </div>
        </div>
    `;
    RecChkSplitClick();
    document.getElementById("submitBtn_2").style.display= "none";
    document.getElementById("submitBtn_3").style.display= null;
};

function splitLayout3_cstm(){
    showLayout3();
    document.getElementById("splitLayout3").innerHTML = `
    <div class="mt-6">
        <div class="grid gap-2 mb-4 md:grid-cols-1">
            <div>
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Custom Pages</label>
                <input type="text" id="customPage" name="customPage" class="mt-4 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-4/6 p-2.5" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all">
            </div>
            <div class="flex items-center mt-2">
                <input id="mergePDF" name="mergePDF" type="checkbox" class="w-4 h-4 text-sky-400 bg-gray-300 border-sky-400 rounded focus:ring-sky-400 focus:ring-2">
                <label for="mergePDF" class="ml-2 text-xs font-poppins text-gray-900">Merge all pages into one PDF file.</label>
            </div>
        </div>
    </div>
    `;
    RecChkSplitClick2();

};

function splitLayout3_wthn(){
    showLayout3();
    document.getElementById("splitLayout3").innerHTML = `
        <div class="mt-6">
            <div class="grid mb-4 grid-cols-1 gap-4 xl:gap-8 xl:grid-cols-3">
                <div>
                    <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">First Pages</label>
                    <input type="number" id="fromPage" name="fromPage" class="mt-4 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-fit p-2.5" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="2" placeholder="1">
                </div>
                <div>
                    <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Last Pages</label>
                    <input type="number" id="toPage" name="toPage" class="mt-4 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-fit p-2.5" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="2" placeholder="10">
                </div>
            </div>
            <div class="flex items-center mt-2">
                <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="w-4 h-4 text-sky-400 bg-gray-300 border-sky-400 rounded focus:ring-sky-400 focus:ring-2">
                <label for="mergePDF" class="ml-2 text-xs font-poppins text-gray-900">Merge all pages into one PDF file.</label>
            </div>
        </div>
    `;
    LowChkSplitClick2();
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function showLayout3() {
    var layout = document.getElementById('splitLayout3');
    layout.style = null
}

function init() {
    var fullPath = document.getElementById('caption').value;
    var splitLayout = document.getElementById('splitLayout1')
    document.getElementById("fileAlt").style.display = "none";
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#38bdf8"
        splitLayout.style = null
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
        document.getElementById('submitBtn').style.color="#e2e8f0"
        splitLayout.style.display="none"
    }
}

init();
