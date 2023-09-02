function showLayout() {
    var layout = document.getElementById('wmLayout1');
    layout.style = null
}

function showVal(newVal){
    document.getElementById("TransparencyValue").innerHTML="Watermark Transparency <b>"+newVal+" %</b>";
}

function remove_wm() {
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    pdfComp.style.display="none";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}

function wmLayout_image(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
        <div id="wmLayout1" style="">
            <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="image">
            <label class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white" for="wm_file_input">Watermark Image</label>
            <input class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/*">
            <p class="mt-1 font-poppins text-sm text-gray-500 dark:text-gray-300" id="wm_file_input_help">Image (Max. 5 MB)</p>
            <div>
                <label class="mt-4 mb-2 block font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="watermarkLayerStyle">Watermark Layer</label>
                <select id="watermarkLayoutStyle" name="watermarkLayoutStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option selected="">Choose layer style</option>
                    <option value="above">Above the document content</option>
                    <option value="below">Below the document content</option>
                </select>
            </div>
            <div class="mt-4">
                <label class="mb-2 block font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="watermarkPagesStyle">Watermark Pages</label>
                <input type="text" id="watermarkPage" name="watermarkPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1,5-7 or all">
            </div>
            <div class="mt-4">
                <label class="mt-4 mb-2 block font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="watermarkOrientationStyle">Watermark Orientation</label>
                <select id="watermarkRotation" name="watermarkRotation" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                    <option selected="">Choose orientaion degrees</option>
                    <option value="45">45 degrees</option>
                    <option value="90">90 degrees</option>
                    <option value="180">180 degrees</option>
                    <option value="270">270 degrees</option>
                </select>
            </div>
            <div class="mt-4">
                <label id="TransparencyValue" class="mt-4 mb-2 block font-poppins text-sm font-semibold text-slate-900 dark:text-white" for="watermarkFontTransparency">Watermark Transparency</label>
                <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" oninput="showVal(this.value)" onchange="showVal(this.value)">
            </div>
            <div class="mt-4">
                <div class="flex">
                    <div class="flex items-center h-5">
                        <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    </div>
                    <div class="ml-2 text-sm">
                        <label for="isMosaic" class="font-medium text-gray-900 dark:text-gray-300">Mosaic Effects</label>
                        <p id="isMosaicText" class="text-xs font-normal text-gray-500 dark:text-gray-300">It will stamp a 3x3 matrix mosaic of watermark into your document</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function wmLayout_text(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
    <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="text">
    <div class="mt-2">
        <label for="watermarkFontFamily" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Font Family</label>
        <select id="watermarkFontFamily" name="watermarkFontFamily" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option selected>Choose font family</option>
            <option value="Arial">Arial</option>
            <option value="Arial Unicode MS">Arial Unicode MS</option>
            <option value="Comic Sans MS">Comic Sans MS</option>
            <option value="Courier">Courier</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Verdana">Verdana</option>
        </select>
    </div>
    <div class="mt-4">
        <label for="watermarkFontSize" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Font Size</label>
        <input type="text" id="watermarkFontSize" name="watermarkFontSize" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="12">
    </div>
    <div class="mt-4">
        <label for="watermarkFontStyle" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Font Style</label>
        <select id="watermarkFontStyle" name="watermarkFontStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option selected>Choose font style</option>
            <option value="Regular">Regular</option>
            <option value="Bold">Bold</option>
            <option value="Italic">Italic</option>
        </select>
    </div>
    <div class="mt-4">
        <label for="watermarkLayoutStyle" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Layer</label>
        <select id="watermarkLayoutStyle" name="watermarkLayoutStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option selected>Choose layer style</option>
            <option value="above">Above the document content</option>
            <option value="below">Below the document content</option>
        </select>
    </div>
    <div class="mt-4">
        <label for="watermarkPage" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Pages</label>
        <input type="text" id="watermarkPage" name="watermarkPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1,5-7 or all">
    </div>
    <div class="mt-4">
        <label for="watermarkRotation" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Orientation</label>
        <select id="watermarkRotation" name="watermarkRotation" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
            <option selected>Choose orientation degrees</option>
            <option value="45">45 degrees</option>
            <option value="90">90 degrees</option>
            <option value="180">180 degrees</option>
            <option value="270">270 degrees</option>
        </select>
    </div>
    <div class="mt-4">
        <label for="watermarkText" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Text</label>
        <input type="text" id="watermarkText" name="watermarkText" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Eureka">
    </div>
    <div class="mt-4">
        <label for="watermarkFontTransparency" id="TransparencyValue" class="block mt-2 mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white">Watermark Transparency</label>
        <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" oninput="showVal(this.value)" onchange="showVal(this.value)">
    </div>
    <div class="mt-4">
        <div class="flex">
            <div class="flex items-center h-5">
                <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
            </div>
            <div class="ml-2 text-sm">
                <label for="isMosaic" class="font-medium text-gray-900 dark:text-gray-300">Mosaic Effects</label>
                <p id="isMosaicText" class="text-xs font-normal text-gray-500 dark:text-gray-300">It will stamp a 3x3 matrix mosaic of watermark into your document</p>
            </div>
        </div>
    </div>
    `;
};
