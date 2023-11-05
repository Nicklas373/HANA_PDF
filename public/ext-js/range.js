function showLayout() {
    var layout = document.getElementById('wmLayout1');
    layout.style = null
}

function showVal(newVal){
    document.getElementById("TransparencyValue").innerHTML=newVal+" %";
}

function remove_wm() {
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    pdfComp.style.display="none";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}

function LowChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt").style.color = '#38bdf8'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
}

function RecChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt").style.color = '#38bdf8'
}

function LowChkSplit2Click() {
    document.getElementById("lowestChk2").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt2").style.color = '#38bdf8'
    document.getElementById("recChk2").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt2").style.color = '#6b7280'
}

function RecChkSplit2Click() {
    document.getElementById("lowestChk2").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt2").style.color = '#6b7280'
    document.getElementById("recChk2").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt2").style.color = '#38bdf8'
}

function LowChkSplit3Click() {
    document.getElementById("lowestChk3").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt3").style.color = '#38bdf8'
    document.getElementById("recChk3").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt3").style.color = '#6b7280'
    document.getElementById("hiChk3").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt3").style.color = '#6b7280'
    document.getElementById("ulChk3").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt3").style.color = '#6b7280'
}

function RecChkSplit3Click() {
    document.getElementById("lowestChk3").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt3").style.color = '#6b7280'
    document.getElementById("recChk3").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt3").style.color = '#38bdf8'
    document.getElementById("hiChk3").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt3").style.color = '#6b7280'
    document.getElementById("ulChk3").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt3").style.color = '#6b7280'
}
function HiChkSplit3Click() {
    document.getElementById("lowestChk3").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt3").style.color = '#6b7280'
    document.getElementById("recChk3").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt3").style.color = '#6b7280'
    document.getElementById("hiChk3").style.borderColor = '#38bdf8'
    document.getElementById("hi-txt3").style.color = '#38bdf8'
    document.getElementById("ulChk3").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt3").style.color = '#6b7280'
}

function UlChkSplit3Click() {
    document.getElementById("lowestChk3").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt3").style.color = '#6b7280'
    document.getElementById("recChk3").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt3").style.color = '#6b7280'
    document.getElementById("hiChk3").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt3").style.color = '#6b7280'
    document.getElementById("ulChk3").style.borderColor = '#38bdf8'
    document.getElementById("ul-txt3").style.color = '#38bdf8'
}

function LowChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt4").style.color = '#38bdf8'
    document.getElementById("recChk4").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt4").style.color = '#6b7280'
    document.getElementById("hiChk4").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt4").style.color = '#6b7280'
    document.getElementById("ulChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt4").style.color = '#6b7280'
    document.getElementById("srChk4").style.borderColor = '#e2e8f0'
    document.getElementById("sr-txt4").style.color = '#6b7280'
    document.getElementById("ssrChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ssr-txt4").style.color = '#6b7280'
}

function RecChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt4").style.color = '#6b7280'
    document.getElementById("recChk4").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt4").style.color = '#38bdf8'
    document.getElementById("hiChk4").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt4").style.color = '#6b7280'
    document.getElementById("ulChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt4").style.color = '#6b7280'
    document.getElementById("srChk4").style.borderColor = '#e2e8f0'
    document.getElementById("sr-txt4").style.color = '#6b7280'
    document.getElementById("ssrChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ssr-txt4").style.color = '#6b7280'
}
function HiChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt4").style.color = '#6b7280'
    document.getElementById("recChk4").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt4").style.color = '#6b7280'
    document.getElementById("hiChk4").style.borderColor = '#38bdf8'
    document.getElementById("hi-txt4").style.color = '#38bdf8'
    document.getElementById("ulChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt4").style.color = '#6b7280'
    document.getElementById("srChk4").style.borderColor = '#e2e8f0'
    document.getElementById("sr-txt4").style.color = '#6b7280'
    document.getElementById("ssrChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ssr-txt4").style.color = '#6b7280'
}

function UlChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt4").style.color = '#6b7280'
    document.getElementById("recChk4").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt4").style.color = '#6b7280'
    document.getElementById("hiChk4").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt4").style.color = '#6b7280'
    document.getElementById("ulChk4").style.borderColor = '#38bdf8'
    document.getElementById("ul-txt4").style.color = '#38bdf8'
    document.getElementById("srChk4").style.borderColor = '#e2e8f0'
    document.getElementById("sr-txt4").style.color = '#6b7280'
    document.getElementById("ssrChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ssr-txt4").style.color = '#6b7280'
}

function SRChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt4").style.color = '#6b7280'
    document.getElementById("recChk4").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt4").style.color = '#6b7280'
    document.getElementById("hiChk4").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt4").style.color = '#6b7280'
    document.getElementById("ulChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt4").style.color = '#6b7280'
    document.getElementById("srChk4").style.borderColor = '#38bdf8'
    document.getElementById("sr-txt4").style.color = '#38bdf8'
    document.getElementById("ssrChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ssr-txt4").style.color = '#6b7280'
}

function SSRChkSplit4Click() {
    document.getElementById("lowestChk4").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt4").style.color = '#6b7280'
    document.getElementById("recChk4").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt4").style.color = '#6b7280'
    document.getElementById("hiChk4").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt4").style.color = '#6b7280'
    document.getElementById("ulChk4").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt4").style.color = '#6b7280'
    document.getElementById("srChk4").style.borderColor = '#e2e8f0'
    document.getElementById("sr-txt4").style.color = '#6b7280'
    document.getElementById("ssrChk4").style.borderColor = '#38bdf8'
    document.getElementById("ssr-txt4").style.color = '#38bdf8'
}

function LowChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt5").style.color = '#38bdf8'
    document.getElementById("recChk5").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt5").style.color = '#6b7280'
    document.getElementById("hiChk5").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt5").style.color = '#6b7280'
}

function RecChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt5").style.color = '#6b7280'
    document.getElementById("recChk5").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt5").style.color = '#38bdf8'
    document.getElementById("hiChk5").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt5").style.color = '#6b7280'
}
function HiChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt5").style.color = '#6b7280'
    document.getElementById("recChk5").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt5").style.color = '#6b7280'
    document.getElementById("hiChk5").style.borderColor = '#38bdf8'
    document.getElementById("hi-txt5").style.color = '#38bdf8'
}

function LowChkSplit6Click() {
    document.getElementById("lowestChk6").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt6").style.color = '#38bdf8'
    document.getElementById("recChk6").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt6").style.color = '#6b7280'
}

function RecChkSplit6Click() {
    document.getElementById("lowestChk6").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt6").style.color = '#6b7280'
    document.getElementById("recChk6").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt6").style.color = '#38bdf8'
}

function LowChkSplit7Click() {
    document.getElementById("lowestChk7").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt7").style.color = '#38bdf8'
    document.getElementById("recChk7").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt7").style.color = '#6b7280'
    document.getElementById("hiChk7").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt7").style.color = '#6b7280'
    document.getElementById("ulChk7").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt7").style.color = '#6b7280'
}

function RecChkSplit7Click() {
    document.getElementById("lowestChk7").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt7").style.color = '#6b7280'
    document.getElementById("recChk7").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt7").style.color = '#38bdf8'
    document.getElementById("hiChk7").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt7").style.color = '#6b7280'
    document.getElementById("ulChk7").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt7").style.color = '#6b7280'
}

function HiChkSplit7Click() {
    document.getElementById("lowestChk7").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt7").style.color = '#6b7280'
    document.getElementById("recChk7").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt7").style.color = '#6b7280'
    document.getElementById("hiChk7").style.borderColor = '#38bdf8'
    document.getElementById("hi-txt7").style.color = '#38bdf8'
    document.getElementById("ulChk7").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt7").style.color = '#6b7280'
}

function UlChkSplit7Click() {
    document.getElementById("lowestChk7").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt7").style.color = '#6b7280'
    document.getElementById("recChk7").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt7").style.color = '#6b7280'
    document.getElementById("hiChk7").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt7").style.color = '#6b7280'
    document.getElementById("ulChk7").style.borderColor = '#38bdf8'
    document.getElementById("ul-txt7").style.color = '#38bdf8'
}

function wmLayout_image(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
        <div class="mt-6 mb-4" id="wmLayout1" style="">
            <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="image">
            <div class="mt-4 mb-8">
                <label class="block mb-4 font-poppins text-base font-semibold text-slate-900" for="wm_file_input">Image</label>
                <input class="block w-5/6 font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg shadow-inner cursor-pointer" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/*">
                <p class="mt-1 font-poppins text-sm text-gray-500" id="file_input_help">Image (Max. 25 MB)</p>
            </div>
            <div class="mt-4 mb-8">
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Layer</label>
                <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4">
                    <li id="lowestChk2" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkLayoutStyle" name="watermarkLayoutStyle" value="above" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit2Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt2">Above content</label>
                            </div>
                        </div>
                    </li>
                    <li id="recChk2" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkLayoutStyle" name="watermarkLayoutStyle" value="below" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit2Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt2">Below content</label>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="grid gap-2 mb-8 grid-cols-1">
                <div>
                    <label class="block mb-2 font-poppins text-base font-semibold text-slate-900" for="watermarkPagesStyle">Pages</label>
                    <input type="text" id="watermarkPage" name="watermarkPage" class="mt-4 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-5/6 p-2.5" placeholder="1,2,3 or 1-5 or 1,2-5 or all">
                </div>
            </div>
            <div class="mt-4 mb-8">
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Orientation</label>
                <ul class="grid grid-cols-1 xl:grid-cols-4 gap-2 xl:gap-4">
                    <li id="lowestChk3" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkRotation" name="watermarkRotation" value="45" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit3Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt3">45°</label>
                            </div>
                        </div>
                    </li>
                    <li id="recChk3" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkRotation" name="watermarkRotation" value="90" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit3Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt3">90°</label>
                            </div>
                        </div>
                    </li>
                    <li id="hiChk3" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkRotation" name="watermarkRotation" value="180" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="HiChkSplit3Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="hi-txt3">180°</label>
                            </div>
                        </div>
                    </li>
                    <li id="ulChk3" class="border border-slate-200 p-2 mt-2 rounded">
                        <div class="flex">
                            <div class="flex items-center h-5">
                                <input id="watermarkRotation" name="watermarkRotation" value="270" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="UlChkSplit3Click()">
                            </div>
                            <div class="ml-4">
                                <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="ul-txt3">270°</label>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            <div class="grid gap-2 mb-8 grid-cols-1">
                <div>
                    <label id="Transparency" class="block mb-2 font-poppins text-base font-semibold text-slate-900" for="watermarkFontTransparency">Transparency</label>
                    <div class="grid w-full grid-cols-2 gap-4">
                        <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer oninput="showVal(this.value)" onchange="showVal(this.value)">
                        <label id="TransparencyValue" class="block mt-2.5 font-poppins font-semibold text-sm text-gray-500" for="watermarkFontTransparency"></label>
                    </div>
                </div>
            </div>
            <div class="mt-6">
                <div class="flex">
                    <div class="flex items-center h-5">
                        <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="w-4 h-4 text-sky-400 bg-gray-300 border-sky-400 rounded focus:ring-sky-400 focus:ring-2">
                    </div>
                    <div class="ml-2 text-sm">
                        <label for="isMosaic" class="font-semibold text-sm text-slate-800 font-poppins">Mosaic Effects</label>
                        <p id="isMosaicText" class="text-xs mt-1 font-normal font-poppins text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    LowChkSplitClick();
}

function wmLayout_text(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
    <div class="mt-6 mb-4">
        <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="text">
        <div class="mt-4 mb-8">
            <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Font Family</label>
            <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2">
                <li id="lowestChk4" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Arial" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt4">Arial</label>
                        </div>
                    </div>
                </li>
                <li id="recChk4" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Arial Unicode MS" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt4">Arial Unicode MS</label>
                        </div>
                    </div>
                </li>
                <li id="hiChk4" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Comic Sans MS" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="HiChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="hi-txt4">Comic Sans MS</label>
                        </div>
                    </div>
                </li>
                <li id="ulChk4" class="border border-slate-200 p-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Courier" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="UlChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="ul-txt4">Courier</label>
                        </div>
                    </div>
                </li>
                <li id="srChk4" class="border border-slate-200 p-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Times New Roman" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="SRChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="sr-txt4">Times New Roman</label>
                        </div>
                    </div>
                </li>
                <li id="ssrChk4" class="border border-slate-200 p-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontFamily" name="watermarkFontFamily" value="Verdana" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="SSRChkSplit4Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="ssr-txt4">Verdana</label>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Text</label>
                <input type="text" id="watermarkText" name="watermarkText" class="font-poppins mt-4 block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Lorem ipsum dolor sit amet, consectetur adipiscing elit" />
            </div>
            <div>
                <label class="font-poppins mb-2 mt-2 block text-sm font-semibold text-slate-900" style="visibility: hidden;">Pages</label>
                <div class="grid w-fit grid-cols-2 gap-2">
                    <input type="text" id="watermarkPage" name="watermarkPage" class="mt-1 bg-gray-50 border border-gray-300 text-slate-700 font-poppins text-xs rounded-lg focus:ring-sky-400 focus:border-sky-400 block w-full p-2.5" placeholder="1,2,3 or 1-5 or 1,2-5 or all">
                    <label class="font-poppins mt-3.5 block text-sm font-semibold text-gray-500">Pages</label>
                </div>
            </div>
        </div>
        <div class="mt-4 mb-8">
            <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Font Style</label>
            <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4">
                <li id="lowestChk5" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontStyle" name="watermarkFontStyle" value="Regular" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit5Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt5">Regular</label>
                        </div>
                    </div>
                </li>
                <li id="recChk5" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontStyle" name="watermarkFontStyle" value="Bold" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit5Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt5">Bold</label>
                        </div>
                    </div>
                </li>
                <li id="hiChk5" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkFontStyle" name="watermarkFontStyle" value="Italic" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="HiChkSplit5Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="hi-txt5">Italic</label>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="mt-4 mb-8">
            <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Layer</label>
            <ul class="grid grid-cols-1 xl:grid-cols-3 gap-2 xl:gap-4">
                <li id="lowestChk6" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkLayoutStyle" name="watermarkLayoutStyle" value="above" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit6Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt6">Above content</label>
                        </div>
                    </div>
                </li>
                <li id="recChk6" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkLayoutStyle" name="watermarkLayoutStyle" value="below" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit6Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt6">Below content</label>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="mt-4 mb-8">
            <label class="block mb-2 font-poppins text-base font-semibold text-slate-900">Orientation</label>
            <ul class="grid grid-cols-1 xl:grid-cols-4 gap-2 xl:gap-4">
                <li id="lowestChk7" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkRotation" name="watermarkRotation" value="45" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="LowChkSplit7Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="lowest-txt7">45°</label>
                        </div>
                    </div>
                </li>
                <li id="recChk7" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkRotation" name="watermarkRotation" value="90" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="RecChkSplit7Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="rec-txt7">90°</label>
                        </div>
                    </div>
                </li>
                <li id="hiChk7" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkRotation" name="watermarkRotation" value="180" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="HiChkSplit7Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="hi-txt7">180°</label>
                        </div>
                    </div>
                </li>
                <li id="ulChk7" class="border border-slate-200 p-2 mt-2 rounded">
                    <div class="flex">
                        <div class="flex items-center h-5">
                            <input id="watermarkRotation" name="watermarkRotation" value="270" aria-describedby="helper-radio-text" type="radio" class="w-4 h-4 text-sky-400 border-sky-400 ring-sky-400 focus:ring-sky-400 focus:ring-2" onclick="UlChkSplit7Click()">
                        </div>
                        <div class="ml-4">
                            <label for="helper-radio" class="font-semibold text-sm text-gray-500 font-poppins" id="ul-txt7">270°</label>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <div class="grid gap-2 mb-8 grid-cols-1">
            <div>
                <label id="Transparency" class="block mb-2 font-poppins text-base font-semibold text-slate-900" for="watermarkFontTransparency">Transparency</label>
                <div class="grid w-full grid-cols-2 gap-x-4">
                    <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer oninput="showVal(this.value)" onchange="showVal(this.value)">
                    <label id="TransparencyValue" class="block mt-2.5 font-poppins font-semibold text-sm text-gray-500" for="watermarkFontTransparency"></label>
                </div>
            </div>
        </div>
        <div class="mt-6">
            <div class="flex">
                <div class="flex items-center h-5">
                    <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="w-4 h-4 text-sky-400 bg-gray-300 border-sky-400 rounded focus:ring-sky-400 focus:ring-2">
                </div>
                <div class="ml-2 text-sm">
                    <label for="isMosaic" class="font-semibold text-sm text-slate-800 font-poppins">Mosaic Effects</label>
                    <p id="isMosaicText" class="text-xs mt-1 font-normal font-poppins text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
                </div>
            </div>
        </div>
    </div>
    `;
    RecChkSplitClick();
};

function init() {
    var fullPath = document.getElementById('caption').value;
    var btnLayout = document.getElementById('submitBtn')
    var mergeLayout = document.getElementById('submitBtn_1')
    var pdfLayout = document.getElementById('pdfCompLayout')
    document.getElementById("fileAlt").style.display = "none";
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#38bdf8"
        document.getElementById('file_input').style.backgroundColor="#f8fafc"
        btnLayout.style = null
        pdfLayout.style = null
        mergeLayout.style = "none"
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
        document.getElementById('submitBtn').style.color="#ffffff"
        document.getElementById('file_input').style.backgroundColor="#e2e8f0"
        btnLayout.style.display = "none"
        mergeLayout.style = null
        pdfLayout.style.display="none"
    }
}

init();
