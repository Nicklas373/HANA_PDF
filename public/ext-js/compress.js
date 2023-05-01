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

function init() {
    var fullPath = document.getElementById('caption').value;
    var pdfLayout = document.getElementById('pdfCompLayout')
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#0f172a"
        document.getElementById('submitBtn').style.color="#ffffff"
        pdfLayout.style.visibility="visible"
        var pdfComp = document.getElementById('pdfComp')
        var pdfSubmit = document.getElementById('pdfSubmit')
        pdfComp.innerHTML = `
            <h3 class="mb-5 mt-2 font-poppins font-medium text-gray-900 dark:text-white">Compression Level</h3>
            <ul class="grid w-full gap-4 lg:grid-cols-1 2xl:grid-cols-3 mt-4">
                <li>
                    <input type="radio" id="comp-low" name="compMethod" value="low" class="hidden peer">
                    <label for="comp-low" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">                           
                        <div class="block">
                            <div class="w-full text-lg font-semibold">Lowest</div>
                            <div class="w-full">High quality, less compression</div>
                        </div>
                        <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </label>
                </li>
                <li>
                    <input type="radio" id="comp-rec" name="compMethod" value="recommended" class="hidden peer">
                    <label for="comp-rec" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">                           
                        <div class="block">
                            <div class="w-full text-lg font-semibold">Recommended</div>
                            <div class="w-full">Good quality, good compression</div>
                        </div>
                        <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </label>
                </li>
                <li>
                    <input type="radio" id="comp-high" name="compMethod" value="extreme" class="hidden peer">
                    <label for="comp-high" class="inline-flex items-center justify-between w-full p-5 text-slate-200 bg-slate-900 border border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-600 peer-checked:text-slate-900 peer-checked:bg-slate-200 hover:text-slate-900 hover:bg-slate-200">                           
                        <div class="block">
                            <div class="w-full text-lg font-semibold">High</div>
                            <div class="w-full">Less quality, high compression</div>
                        </div>
                        <svg aria-hidden="true" class="w-6 h-6 ml-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M12.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </label>
                </li>
            </ul>
        `
        pdfSubmit.innerHTML = `
            <button type="submit" name="formAction" id="compBtn" class="w-full h-16 font-poppins text-slate-200 bg-slate-900 rounded-lg cursor-pointer font-medium p-4 text-center" onClick="onClick()" value="compress">Compress PDF</button>
        `
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#0f172a"
        pdfLayout.style.visibility="hidden"
        var pdfComp = document.getElementById('pdfComp')
        var pdfSubmit = document.getElementById('pdfSubmit')
        pdfComp.innerHTML = `
            <div id="pdfComp" name="pdfComp"></div>
        `
        pdfSubmit.innerHTML = `
            <div id="pdfSubmit" name="pdfSubmit"></div>
        `
    }
}

init();