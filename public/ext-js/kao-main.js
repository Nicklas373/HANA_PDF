function changeButtonColor(kaoInput) {
    if (kaoInput == 'kaoA') {
        document.getElementById('file_input').addEventListener('change', function(e) {
            var fullPath = document.getElementById('file_input').value;
            if (fullPath) {
                var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                var filename = fullPath.substring(startIndex);
                if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                    filename = filename.substring(1);
                }
                if (filename !== "") {
                    document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                    document.getElementById('submitBtn').style.color="white"
                } else {
                    document.getElementById('submitBtn').style.backgroundColor="transparent"
                    document.getElementById('submitBtn').style.color="#38bdf8"
                }
            }
        });
    } else if (kaoInput == 'kaoB') {
        document.getElementById('multiple_files').addEventListener('change', function(e) {
            var fullPath = document.getElementById('multiple_files').value;
            if (fullPath) {
                var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                var filename = fullPath.substring(startIndex);
                if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                    filename = filename.substring(1);
                }
                if (filename == "") {
                    document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
                    document.getElementById('submitBtn').style.color="#38bdf8"
                    document.getElementById('multiple_files').style.backgroundColor="#e2e8f0"
                } else {
                    document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                    document.getElementById('submitBtn').style.color="#ffffff"
                    document.getElementById('multiple_files').style.backgroundColor="#f8fafc"
                }
            }
        });
    } else if (kaoInput == 'kaoC') {
        var fullPath = document.getElementById('urlToPDF').value;
            if (fullPath) {
                var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
                var filename = fullPath.substring(startIndex);
                if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                    filename = filename.substring(1);
                }
                if (filename !== "") {
                    document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                    document.getElementById('submitBtn').style.color="white"
                } else {
                    document.getElementById('submitBtn').style.backgroundColor="transparent"
                    document.getElementById('submitBtn').style.color="#38bdf8"
                }
            }
    }
}

function checkValidation(validation) {
    if (validation == 'compMethod') {
        if (!document.getElementById('comp-low').checked && !document.getElementById('comp-rec').checked && !document.getElementById('comp-high').checked) {
            document.getElementById('lowestChk').style.borderColor = '#dc2626'
            document.getElementById('recChk').style.borderColor = '#dc2626'
            document.getElementById('highestChk').style.borderColor = '#dc2626'
        } else {
            if (document.getElementById('comp-low').checked) {
                document.getElementById("lowestChk").style.borderColor = '#38bdf8'
                document.getElementById("lowest-txt").style.color = '#38bdf8'
                document.getElementById("recChk").style.borderColor = '#e2e8f0'
                document.getElementById("rec-txt").style.color = '#1e293b'
                document.getElementById("highestChk").style.borderColor = '#e2e8f0'
                document.getElementById("highest-txt").style.color = '#1e293b'
            } else if (document.getElementById('comp-rec').checked) {
                document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
                document.getElementById("lowest-txt").style.color = '#1e293b'
                document.getElementById("recChk").style.borderColor = '#38bdf8'
                document.getElementById("rec-txt").style.color = '#38bdf8'
                document.getElementById("highestChk").style.borderColor = '#e2e8f0'
                document.getElementById("highest-txt").style.color = '#1e293b'
            } else if (document.getElementById('comp-high').checked) {
                document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
                document.getElementById("lowest-txt").style.color = '#1e293b'
                document.getElementById("recChk").style.borderColor = '#e2e8f0'
                document.getElementById("rec-txt").style.color = '#1e293b'
                document.getElementById("highestChk").style.borderColor = '#38bdf8'
                document.getElementById("highest-txt").style.color = '#38bdf8'
            }
            document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
            document.getElementById("submitBtn_1").style.color = "white"
        }
    }
    if (validation == 'cnvFrPDF') {
        if (!document.getElementById('lowestChkA').checked && !document.getElementById('ulChkA').checked && !document.getElementById('recChkA').checked && !document.getElementById('highestChkA').checked) {
            document.getElementById('lowestChkA').style.borderColor = '#dc2626'
            document.getElementById('recChkA').style.borderColor = '#dc2626'
            document.getElementById('highestChkA').style.borderColor = '#dc2626'
            document.getElementById('ulChkA').style.borderColor = '#dc2626'
        } else {
            if (document.getElementById('lowestChkA').checked) {
                document.getElementById("lowestChk").style.borderColor = '#38bdf8'
                document.getElementById("lowest-txt").style.color = '#38bdf8'
                document.getElementById("recChk").style.borderColor = '#e2e8f0'
                document.getElementById("rec-txt").style.color = '#1e293b'
                document.getElementById("highestChk").style.borderColor = '#e2e8f0'
                document.getElementById("highest-txt").style.color = '#1e293b'
                document.getElementById("ulChk").style.borderColor = '#e2e8f0'
                document.getElementById("ul-txt").style.color = '#1e293b'
            } else if (document.getElementById('recChkA').checked) {
                document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
                document.getElementById("lowest-txt").style.color = '#1e293b'
                document.getElementById("recChk").style.borderColor = '#38bdf8'
                document.getElementById("rec-txt").style.color = '#38bdf8'
                document.getElementById("highestChk").style.borderColor = '#e2e8f0'
                document.getElementById("highest-txt").style.color = '#1e293b'
                document.getElementById("ulChk").style.borderColor = '#e2e8f0'
                document.getElementById("ul-txt").style.color = '#1e293b'
            } else if (document.getElementById('highestChkA').checked) {
                document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
                document.getElementById("lowest-txt").style.color = '#1e293b'
                document.getElementById("recChk").style.borderColor = '#e2e8f0'
                document.getElementById("rec-txt").style.color = '#1e293b'
                document.getElementById("highestChk").style.borderColor = '#38bdf8'
                document.getElementById("highest-txt").style.color = '#38bdf8'
                document.getElementById("ulChk").style.borderColor = '#e2e8f0'
                document.getElementById("ul-txt").style.color = '#1e293b'
            } else if (document.getElementById('ulChkA').checked) {
                document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
                document.getElementById("lowest-txt").style.color = '#1e293b'
                document.getElementById("recChk").style.borderColor = '#e2e8f0'
                document.getElementById("rec-txt").style.color = '#1e293b'
                document.getElementById("highestChk").style.borderColor = '#e2e8f0'
                document.getElementById("highest-txt").style.color = '#1e293b'
                document.getElementById("ulChk").style.borderColor = '#38bdf8'
                document.getElementById("ul-txt").style.color = '#38bdf8'
            }
            document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
            document.getElementById("submitBtn_1").style.color = "white"
        }
    }
    if (validation == 'extCustomPage' || validation == 'splitCustomPage') {
        if (document.getElementById("customPage").value != '') {
            document.getElementById("customPage").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("customPage").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'splitFirstPage') {
        if (document.getElementById("fromPage").value != '') {
            document.getElementById("fromPage").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("fromPage").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'splitLastPage') {
        if (document.getElementById("toPage").value != '') {
            document.getElementById("toPage").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("toPage").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'watermarkText') {
        if (document.getElementById("watermarkText").value != '') {
            document.getElementById("watermarkText").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("watermarkText").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'watermarkPage') {
        if (document.getElementById("watermarkPage").value != '') {
            document.getElementById("watermarkPage").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("watermarkPage").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'wm_file_input') {
        if (document.getElementById("wm_file_input").value != '') {
            document.getElementById("wm_file_input").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("wm_file_input").style.borderColor = "#dc2626"
        }
    }
    if (validation == 'urlToPDF') {
        if (document.getElementById("urlToPDF").value != '') {
            document.getElementById("urlToPDF").style.borderColor = "#d1d5db"
        } else {
            document.getElementById("urlToPDF").style.borderColor = "#dc2626"
        }
    }
}

function dropdownManage() {
    if (document.getElementById('dropdownNavbarLink').value == "1") {
        document.getElementById('dropdownNavbarImage').style.transform = 'rotate(-90deg)';
        document.getElementById('dropdownNavbarLink').value = "0";
    } else {
        document.getElementById('dropdownNavbarImage').style.transform = 'rotate(0deg)';
        document.getElementById('dropdownNavbarLink').value = "1";

    }
}

function dropdownCnvToPDF() {
    if (document.getElementById('cnvToPDFdropdown').value == "1") {
        document.getElementById('cnvToPDFdropdownImage').style.transform = 'rotate(-90deg)';
        document.getElementById('cnvToPDFdropdown').value = "0";
    } else {
        if (document.getElementById('cnvFromPDFdropdown').value == "1") {
            document.getElementById('cnvFromPDFdropdownImage').style.transform = 'rotate(-90deg)';
            document.getElementById('cnvFromPDFdropdown').value = "0";
        }
        document.getElementById('cnvToPDFdropdownImage').style.transform = 'rotate(0deg)';
        document.getElementById('cnvToPDFdropdown').value = "1";

    }
}

function dropdownCnvFromPDF() {
    if (document.getElementById('cnvFromPDFdropdown').value == "1") {
        document.getElementById('cnvFromPDFdropdownImage').style.transform = 'rotate(-90deg)';
        document.getElementById('cnvFromPDFdropdown').value = "0";
    } else {
        if (document.getElementById('cnvToPDFdropdown').value == "1") {
            document.getElementById('cnvToPDFdropdownImage').style.transform = 'rotate(-90deg)';
            document.getElementById('cnvToPDFdropdown').value = "0";
        }
        document.getElementById('cnvFromPDFdropdownImage').style.transform = 'rotate(0deg)';
        document.getElementById('cnvFromPDFdropdown').value = "1";
    }
}

function init() {
    if (document.getElementById('cnvFrPDF') !== null || document.getElementById('compPDF') !== null || document.getElementById('cnvToPDF') !== null) {
        var fullPath = document.getElementById('caption');
        var pdfLayout = document.getElementById('pdfCompLayout');
        var btnLayout = document.getElementById('submitBtn_1');
        if (fullPath) {
            document.getElementById("fileAlt").style.display = "none";
            if (fullPath.value !== '') {
                document.getElementById('submitBtn').style.backgroundColor="transparent"
                document.getElementById('submitBtn').style.color="#38bdf8"
                pdfLayout.style = null
                btnLayout.style = null
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="transparent"
                pdfLayout.style.display = "none"
                btnLayout.style.display = "none"
            }
        }
    } else if (document.getElementById("multiple_files") !== null) {
        var fullPath = document.getElementById('caption');
        var btnLayout = document.getElementById('submitBtn')
        var mergeLayout = document.getElementById('submitBtn_1')
        if (fullPath) {
            if (fullPath.value !== '') {
                document.getElementById("fileAlt").style.display = "none";
                document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
                document.getElementById('submitBtn').style.color="#38bdf8"
                document.getElementById('multiple_files').style.backgroundColor="#f8fafc"
                btnLayout.style = null
                mergeLayout.style = "none"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="#e2e8f0"
                document.getElementById('multiple_files').style.backgroundColor="#e2e8f0"
                btnLayout.style.display = "none"
                mergeLayout.style = null
            }
        }
    } else if (document.getElementById("splitLayout1") !== null) {
        var fullPath = document.getElementById('caption')
        if (fullPath) {
            var splitLayout = document.getElementById('splitLayout1')
            document.getElementById("fileAlt").style.display = "none";
            if (fullPath.value !== '') {
                document.getElementById('submitBtn').style.backgroundColor="transparent"
                document.getElementById('submitBtn').style.color="#38bdf8"
                splitLayout.style = null
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="transparent"
                splitLayout.style.display="none"
            }
        }
    } else if (document.getElementById("wmLayout1") !== null) {
        var fullPath = document.getElementById('caption')
        var btnLayout = document.getElementById('submitBtn')
        var mergeLayout = document.getElementById('submitBtn_1')
        var pdfLayout = document.getElementById('pdfCompLayout')
        if (fullPath) {
            document.getElementById("fileAlt").style.display = "none";
            if (fullPath.value !== '') {
                document.getElementById('submitBtn').style.backgroundColor="transparent"
                document.getElementById('submitBtn').style.color="#38bdf8"
                btnLayout.style = null
                pdfLayout.style = null
                mergeLayout.style = "none"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="transparent"
                btnLayout.style.display = "none"
                mergeLayout.style = null
                pdfLayout.style.display="none"
            }
        }
    } else if (document.getElementById("api") !== null) {
        var currentUsage = document.getElementById("progressValue").textContent;
        var limitUsage = 250;
        var totalUsagePercentage = (currentUsage*100)/limitUsage;
        document.getElementById("progressValue").innerText = currentUsage + "/250";
        document.getElementById("progressBar").style.width = totalUsagePercentage.toFixed(2) + "%";
    }
}

function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

function generateMesssage(subMessage) {
    var ul = document.getElementById("pre-title");
    var li = document.createElement("li");
    li.id = "pre-list_"+ul.childElementCount;
    li.appendChild(document.createTextNode(subMessage));
    ul.appendChild(li);
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

function HiChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt5").style.color = '#6b7280'
    document.getElementById("recChk5").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt5").style.color = '#6b7280'
    document.getElementById("hiChk5").style.borderColor = '#38bdf8'
    document.getElementById("hi-txt5").style.color = '#38bdf8'
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

function LowChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt").style.color = '#38bdf8'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
}

function LowChkSplit2Click() {
    document.getElementById("lowestChk2").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt2").style.color = '#38bdf8'
    document.getElementById("recChk2").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt2").style.color = '#1e293b'
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

function LowChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt5").style.color = '#38bdf8'
    document.getElementById("recChk5").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt5").style.color = '#6b7280'
    document.getElementById("hiChk5").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt5").style.color = '#6b7280'
}

function LowChkSplit6Click() {
    document.getElementById("lowestChk6").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt6").style.color = '#38bdf8'
    document.getElementById("recChk6").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt6").style.color = '#6b7280'
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

function RecChkSplitClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt").style.color = '#38bdf8'
}

function RecChkSplit2Click() {
    document.getElementById("lowestChk2").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt2").style.color = '#1e293b'
    document.getElementById("recChk2").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt2").style.color = '#38bdf8'
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

function RecChkSplit5Click() {
    document.getElementById("lowestChk5").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt5").style.color = '#6b7280'
    document.getElementById("recChk5").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt5").style.color = '#38bdf8'
    document.getElementById("hiChk5").style.borderColor = '#e2e8f0'
    document.getElementById("hi-txt5").style.color = '#6b7280'
}

function RecChkSplit6Click() {
    document.getElementById("lowestChk6").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt6").style.color = '#6b7280'
    document.getElementById("recChk6").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt6").style.color = '#38bdf8'
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

function reloadIFrame() {
    var iframe = document.getElementById("iFrame");
    if (iframe !== null) {
        if (iframe.contentDocument !== null) {
            console.log(iframe.contentDocument.URL); //work control
            if(iframe.contentDocument.URL == "about:blank"){
                iframe.src =  iframe.src;
            }
            document.getElementById("iFrame").style.display = "none"
            document.getElementById("iFrameBorder").style.display = null
        } else {
            clearInterval(timerId);
            document.getElementById("iFrameBorder").style.display = "none"
            document.getElementById("iFrame").style.display = null
        }
    }

}

function remove_wm() {
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    pdfComp.style.display="none";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}

function showLayout() {
    var layout = document.getElementById('wmLayout1');
    layout.style = null
}

function showLayout3() {
    var layout = document.getElementById('splitLayout3');
    layout.style = null
}

function showVal(newVal){
    document.getElementById("TransparencyValue").innerHTML=newVal+" %";
}

function splitLayout2_split(){
    document.getElementById("splitLayout2").innerHTML = `
    <div class="mb-4 mt-6" id="splitLayout2">
    <div>
      <label for="SplitOpt2a" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Split Options</label>
      <ul id="splitRadio" class="mb-4 mt-4 grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
        <li id="lowestChk2" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="SplitOpt2a" value="selPages" name="SplitOpt2" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="splitLayout3_wthn()" />
            </div>
            <div class="ml-4">
              <label for="SplitOpt2a" class="font-poppins text-sm font-semibold text-slate-800" id="lowest-txt2">Selected Pages</label>
            </div>
          </div>
        </li>
        <li id="recChk2" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="SplitOpt2b" value="cusPages" name="SplitOpt2" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="splitLayout3_cstm()" />
            </div>
            <div class="ml-4">
              <label for="SplitOpt2b" class="font-poppins text-sm font-semibold text-slate-800" id="rec-txt2">Custom Pages</label>
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
     <div class="mb-4 grid gap-2 md:grid-cols-1">
      <div>
        <label for="customPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Custom Pages</label>
        <input type="text" id="customPage" name="customPage" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusIn="checkValidation('extCustomPage')" onFocusOut="checkValidation('extCustomPage')" />
      </div>
      <div class="mt-2 flex items-center">
        <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
        <label for="mergePDF" class="font-poppins ml-2 text-xs text-gray-900">Merge all pages into one PDF file.</label>
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
  <div class="mb-4 grid gap-2 md:grid-cols-1">
    <div>
      <label for="customPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Custom Pages</label>
      <input type="text" id="customPage" name="customPage" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Example: 1,2,3 or 1-5 or 1,2-5 or all" onFocusIn="checkValidation('splitCustomPage')" onFocusOut="checkValidation('splitCustomPage')" />
    </div>
    <div class="mt-2 flex items-center">
      <input id="mergePDF" name="mergePDF" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
      <label for="mergePDF" class="font-poppins ml-2 text-xs text-gray-900">Merge all pages into one PDF file.</label>
    </div>
  </div>
</div>
    `;
    RecChkSplit2Click();
    document.getElementById("submitBtn_2").style.display= "none";
    document.getElementById("submitBtn_3").style.display= null;
};

function splitLayout3_wthn(){
    showLayout3();
    document.getElementById("splitLayout3").innerHTML = `
    <div class="mt-6">
    <div class="mb-4 grid grid-cols-1 gap-4 xl:grid-cols-3 xl:gap-8">
      <div>
        <label for="fromPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">First Pages</label>
        <input type="number" id="fromPage" name="fromPage" class="font-poppins mt-4 block w-fit rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="2" placeholder="1" onFocusIn="checkValidation('splitFirstPage')" onFocusOut="checkValidation('splitFirstPage')" />
      </div>
      <div>
        <label for="toPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Last Pages</label>
        <input type="number" id="toPage" name="toPage" class="font-poppins mt-4 block w-fit rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" oninput="this.value=this.value.slice(0,this.maxLength)" maxlength="2" placeholder="10" onFocusIn="checkValidation('splitLastPage')" onFocusOut="checkValidation('splitLastPage')" />
      </div>
    </div>
    <div class="mt-2 flex items-center">
      <input id="mergePDF" name="mergePDF" type="checkbox" value="true" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
      <label for="mergePDF" class="font-poppins ml-2 text-xs text-gray-900">Merge all pages into one PDF file.</label>
    </div>
  </div>
    `;
    LowChkSplit2Click();
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function wmLayout_image(){
    showLayout();
    document.getElementById("wmLayout1").innerHTML = `
    <div class="mb-4 mt-6" id="wmLayout1" style="">
    <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="image" />
    <div class="mb-8 mt-4">
      <label for="wm_file_input" class="font-poppins mb-4 block text-base font-semibold text-slate-900" for="wm_file_input">Image</label>
      <input class="font-poppins block w-5/6 cursor-pointer rounded-lg border border-gray-300 text-sm text-slate-900 shadow-inner" aria-describedby="wm_file_input_help" id="wm_file_input" name="wmfile" type="file" accept="image/*" onFocusIn="checkValidation('wm_file_input')" onFocusOut="checkValidation('wm_file_input')" />
      <p class="font-poppins mt-1 text-sm text-gray-500" id="file_input_help">Image (Max. 25 MB)</p>
    </div>
    <div class="mb-8 mt-4">
      <label for="watermarkLayoutStyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Layer</label>
      <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
        <li id="lowestChk2" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkLayoutStyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit2Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkLayoutStyleA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt2">Above content</label>
            </div>
          </div>
        </li>
        <li id="recChk2" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkLayoutStyleB" name="watermarkLayoutStyle" value="below" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit2Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkLayoutStyleB" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt2">Below content</label>
            </div>
          </div>
        </li>
      </ul>
    </div>
    <div class="mb-8 mt-4">
      <label for="watermarkPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Pages</label>
      <input type="text" id="watermarkPage" name="watermarkPage" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="1,2,3 or 1-5 or 1,2-5 or all" onfocusin="checkValidation('watermarkPage')" onfocusout="checkValidation('watermarkPage')" />
    </div>
    <div class="mb-8 mt-4">
      <label for="watermarkRotationA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Orientation</label>
      <ul class="grid grid-cols-1 gap-2 xl:grid-cols-4 xl:gap-4">
        <li id="lowestChk3" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkRotationA" name="watermarkRotation" value="0" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit3Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkRotationA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt3">0°</label>
            </div>
          </div>
        </li>
        <li id="recChk3" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkRotationB" name="watermarkRotation" value="90" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit3Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkRotationB" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt3">90°</label>
            </div>
          </div>
        </li>
        <li id="hiChk3" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkRotationC" name="watermarkRotation" value="180" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="HiChkSplit3Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkRotationC" class="font-poppins text-sm font-semibold text-gray-500" id="hi-txt3">180°</label>
            </div>
          </div>
        </li>
        <li id="ulChk3" class="mt-2 rounded border border-slate-200 p-2">
          <div class="flex">
            <div class="flex h-5 items-center">
              <input id="watermarkRotationD" name="watermarkRotation" value="270" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="UlChkSplit3Click()" />
            </div>
            <div class="ml-4">
              <label for="watermarkRotationD" class="font-poppins text-sm font-semibold text-gray-500" id="ul-txt3">270°</label>
            </div>
          </div>
        </li>
      </ul>
    </div>
    <div class="mb-8 grid grid-cols-1 gap-2">
      <div>
        <label id="Transparency" class="font-poppins mb-2 block text-base font-semibold text-slate-900" for="watermarkFontTransparency">Transparency</label>
        <div class="grid w-full grid-cols-2 gap-4">
          <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer oninput="showVal(this.value)" onchange="showVal(this.value)">
          <label id="TransparencyValue" class="font-poppins mt-2.5 block text-sm font-semibold text-gray-500" for="watermarkFontTransparency"></label>
        </div>
      </div>
    </div>
    <div class="mt-6">
      <div class="flex">
        <div class="flex h-5 items-center">
          <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
        </div>
        <div class="ml-2 text-sm">
          <label for="isMosaic" class="font-poppins text-sm font-semibold text-slate-800">Mosaic Effects</label>
          <p id="isMosaicText" class="font-poppins mt-1 text-xs font-normal text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
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
    <div class="mb-4 mt-6">
  <input type="text" id="wmType" name="wmType" class="" placeholder="" style="display:none;" value="text" />
  <div class="mb-8 mt-4">
    <label for="wmType" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Font Family</label>
    <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3">
      <li id="lowestChk4" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyA" name="watermarkFontFamily" value="Arial" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt4">Arial</label>
          </div>
        </div>
      </li>
      <li id="recChk4" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyB" name="watermarkFontFamily" value="Arial Unicode MS" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyB" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt4">Arial Unicode MS</label>
          </div>
        </div>
      </li>
      <li id="hiChk4" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyC" name="watermarkFontFamily" value="Comic Sans MS" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="HiChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyC" class="font-poppins text-sm font-semibold text-gray-500" id="hi-txt4">Comic Sans MS</label>
          </div>
        </div>
      </li>
      <li id="ulChk4" class="rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyD" name="watermarkFontFamily" value="Courier" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="UlChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyD" class="font-poppins text-sm font-semibold text-gray-500" id="ul-txt4">Courier</label>
          </div>
        </div>
      </li>
      <li id="srChk4" class="rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyE" name="watermarkFontFamily" value="Times New Roman" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="SRChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyE" class="font-poppins text-sm font-semibold text-gray-500" id="sr-txt4">Times New Roman</label>
          </div>
        </div>
      </li>
      <li id="ssrChk4" class="rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontFamilyF" name="watermarkFontFamily" value="Verdana" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="SSRChkSplit4Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontFamilyF" class="font-poppins text-sm font-semibold text-gray-500" id="ssr-txt4">Verdana</label>
          </div>
        </div>
      </li>
    </ul>
  </div>
  <div class="mb-8">
    <label for="watermarkText" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Text</label>
    <input type="text" id="watermarkText" name="watermarkText" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="Lorem ipsum dolor sit amet, consectetur adipiscing elit" onfocusin="checkValidation('watermarkText')" onfocusout="checkValidation('watermarkText')" />
  </div>
  <div class="mb-8 mt-4">
    <label for="watermarkPage" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Pages</label>
    <input type="text" id="watermarkPage" name="watermarkPage" class="font-poppins mt-4 block w-4/6 rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-xs text-slate-700 focus:border-sky-400 focus:ring-sky-400" placeholder="1,2,3 or 1-5 or 1,2-5 or all" onfocusin="checkValidation('watermarkPage')" onfocusout="checkValidation('watermarkPage')" />
  </div>
  <div class="mb-8 mt-4">
    <label for="watermarkFontStyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Font Style</label>
    <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
      <li id="lowestChk5" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontStyleA" name="watermarkFontStyle" value="Regular" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit5Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontStyleA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt5">Regular</label>
          </div>
        </div>
      </li>
      <li id="recChk5" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontStyleB" name="watermarkFontStyle" value="Bold" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit5Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontStyleB" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt5">Bold</label>
          </div>
        </div>
      </li>
      <li id="hiChk5" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkFontStyleC" name="watermarkFontStyle" value="Italic" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="HiChkSplit5Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkFontStyleC" class="font-poppins text-sm font-semibold text-gray-500" id="hi-txt5">Italic</label>
          </div>
        </div>
      </li>
    </ul>
  </div>
  <div class="mb-8 mt-4">
    <label for="watermarkLayoutSyleA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Layer</label>
    <ul class="grid grid-cols-1 gap-2 xl:grid-cols-3 xl:gap-4">
      <li id="lowestChk6" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkLayoutSyleA" name="watermarkLayoutStyle" value="above" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit6Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkLayoutSyleA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt6">Above content</label>
          </div>
        </div>
      </li>
      <li id="recChk6" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkLayoutStyleb" name="watermarkLayoutStyle" value="below" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit6Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkLayoutStyleb" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt6">Below content</label>
          </div>
        </div>
      </li>
    </ul>
  </div>
  <div class="mb-8 mt-4">
    <label for="watermarkRotationA" class="font-poppins mb-2 block text-base font-semibold text-slate-900">Orientation</label>
    <ul class="grid grid-cols-1 gap-2 xl:grid-cols-4 xl:gap-4">
      <li id="lowestChk7" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkRotationA" name="watermarkRotation" value="0" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="LowChkSplit7Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkRotationA" class="font-poppins text-sm font-semibold text-gray-500" id="lowest-txt7">0°</label>
          </div>
        </div>
      </li>
      <li id="recChk7" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkRotationB" name="watermarkRotation" value="90" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="RecChkSplit7Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkRotationB" class="font-poppins text-sm font-semibold text-gray-500" id="rec-txt7">90°</label>
          </div>
        </div>
      </li>
      <li id="hiChk7" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkRotationC" name="watermarkRotation" value="180" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="HiChkSplit7Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkRotationC" class="font-poppins text-sm font-semibold text-gray-500" id="hi-txt7">180°</label>
          </div>
        </div>
      </li>
      <li id="ulChk7" class="mt-2 rounded border border-slate-200 p-2">
        <div class="flex">
          <div class="flex h-5 items-center">
            <input id="watermarkRotationD" name="watermarkRotation" value="270" aria-describedby="helper-radio-text" type="radio" class="h-4 w-4 border-sky-400 text-sky-400 ring-sky-400 focus:ring-2 focus:ring-sky-400" onclick="UlChkSplit7Click()" />
          </div>
          <div class="ml-4">
            <label for="watermarkRotationD" class="font-poppins text-sm font-semibold text-gray-500" id="ul-txt7">270°</label>
          </div>
        </div>
      </li>
    </ul>
  </div>
  <div class="mb-8 grid grid-cols-1 gap-2">
    <div>
      <label id="Transparency" class="font-poppins mb-2 block text-base font-semibold text-slate-900" for="watermarkFontTransparency">Transparency</label>
      <div class="grid w-full grid-cols-2 gap-x-4">
        <input id="watermarkFontTransparency" name="watermarkFontTransparency" type="range" min="0" max="100" value="0" step="1" class="w-full h-2 mt-4 accent-sky-600 rounded-lg cursor-pointer oninput="showVal(this.value)" onchange="showVal(this.value)">
        <label id="TransparencyValue" class="font-poppins mt-2.5 block text-sm font-semibold text-gray-500" for="watermarkFontTransparency"></label>
      </div>
    </div>
  </div>
  <div class="mt-6">
    <div class="flex">
      <div class="flex h-5 items-center">
        <input id="isMosaic" aria-describedby="isMosaicText" name="isMosaic" type="checkbox" class="h-4 w-4 rounded border-sky-400 text-sky-400 focus:ring-2 focus:ring-sky-400" />
      </div>
      <div class="ml-2 text-sm">
        <label for="isMosaic" class="font-poppins text-sm font-semibold text-slate-800">Mosaic Effects</label>
        <p id="isMosaicText" class="font-poppins mt-1 text-xs font-normal text-gray-500">It will stamp a 3x3 matrix mosaic of into your document</p>
      </div>
    </div>
  </div>
</div>
    `;
    RecChkSplitClick();
};

var timerId = setInterval("reloadIFrame();", 2000);

if (document.getElementById('multiple_files')) {
    document.getElementById('multiple_files').addEventListener('change', function(e) {
        var list = document.getElementById('filelist');
        var newList = document.getElementById('pre-title');
        if (document.getElementById('multiple_files').value !== '') {
            if (newList.innerHTML !== '') {
                newList.innerHTML = ``;
                for (var i = 0; i < this.files.length; i++) {
                    generateMesssage(this.files[i].name)
                }
            } else {
                for (var i = 0; i < this.files.length; i++) {
                    generateMesssage(this.files[i].name)
                }
            }
            if (newList.innerHTML == '') {
                list.style.display = 'none';
            } else {
                list.style.display = 'block';
            }
        } else {
                list.style.display = 'none';
                newList.innerHTML = ``;
        }
    });
}

init();
