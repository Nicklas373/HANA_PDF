function showLayout() {
    var layout = document.getElementById('wmLayout1');
    layout.style = null
}

function showVal(newVal){
    document.getElementById("TransparencyValue").innerHTML="Font Transparency:  <b>"+newVal+" %</b>";
}

function remove_wm() {
    var btnLayout = document.getElementById('submitBtn')
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    btnLayout.style.display="block";
    pdfComp.style.display="none";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}

function wmLayout_image(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
        <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="image">
        <label class="block mb-2 font-poppins text-sm font-medium font-semibold text-slate-900 dark:text-white" for="wm_file_input">Upload Image file</label>
        <input class="block w-full font-poppins text-sm text-slate-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 dark:text-gray-400 focus:outline-none dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/*">
        <p class="mt-1 font-poppins text-sm text-gray-500 dark:text-gray-300" id="wm_file_input_help">Image (Max. 5 MB).</p>
        <div class="mt-4">
            <label for="watermarkPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Set pages:</label>
            <input type="text" id="watermarkPage" name="watermarkPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1,5-7" required>
        </div>
        <div class="mt-4">
            <label for="watermarkFontTransparency" id="TransparencyValue" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Image Transparency: </label>
            <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" oninput="showVal(this.value)" onchange="showVal(this.value)" required>
        </div>
        <div class="mt-4">
            <label for="watermarkLayoutStyle" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Watermark Layout:</label>
            <select id="watermarkLayoutStyle" name="watermarkLayoutStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
                <option selected>Choose layout style</option>
                <option value="above">Above</option>
                <option value="below">Below</option>
            </select>
        </div>
    `;
}

function wmLayout_text(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
    <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="text">
    <div>
        <label for="watermarkText" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Watermark Text:</label>
        <input type="text" id="watermarkText" name="watermarkText" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="EMSITPRO PDF Tools" required>
    </div>
    <div class="mt-4">
        <label for="watermarkPage" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Set pages:</label>
        <input type="text" id="watermarkPage" name="watermarkPage" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="1,5-7" required>
    </div>
    <div class="mt-4">
        <label for="watermarkFontFamily" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Font Family:</label>
        <select id="watermarkFontFamily" name="watermarkFontFamily" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
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
        <label for="watermarkFontStyle" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Font Style:</label>
        <select id="watermarkFontStyle" name="watermarkFontStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
            <option selected>Choose font style</option>
            <option value="Regular">Regular</option>
            <option value="Bold">Bold</option>
            <option value="Italic">Italic</option>
        </select>
    </div>
    <div class="mt-4">
        <label for="watermarkFontSize" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Font Size:</label>
        <input type="text" id="watermarkFontSize" name="watermarkFontSize" class="bg-gray-50 border border-gray-300 text-gray-900 font-poppins text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="12" required>
    </div>
    <div class="mt-4">
        <label for="watermarkFontTransparency" id="TransparencyValue" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Font Transparency: </label>
        <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700" oninput="showVal(this.value)" onchange="showVal(this.value)" required>
    </div>
    <div class="mt-4">
        <label for="watermarkLayoutStyle" class="block mb-2 font-poppins text-sm font-medium text-gray-900 dark:text-white">Watermark Layout:</label>
        <select id="watermarkLayoutStyle" name="watermarkLayoutStyle" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" required>
            <option selected>Choose layout style</option>
            <option value="above">Above</option>
            <option value="below">Below</option>
        </select>
    </div>
    `;
};