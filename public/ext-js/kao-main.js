if (firstArea) {
    firstArea.onclick = function() {
        firstColumnArea.style.borderColor = '#38bdf8'
        firstAreaText.style.color = '#38bdf8'
        firstAreaInput.checked = true
        if (secondAreaAltInput.value == "comp" || secondAreaAltInput.value == "cnvFrPDF") {
            secondAreaText.style.color = '#1e293b'
        } else {
            secondAreaText.style.color = '#6b7280'
        }
        secondColumnArea.style = null
        if (thirdArea) {
            if (firstAreaAltInput.value !== "split") {
                if (thirdAreaAltInput.value == "comp" || thirdAreaAltInput.value == "cnvFrPDF") {
                    thirdAreaText.style.color = '#1e293b'
                } else {
                    thirdAreaText.style.color = '#6b7280'
                }
                thirdAreaInput.checked = false
                thirdColumnArea.style = null
            } else {
                if (thirdAreaInput.checked == true) {
                    thirdColumnArea.style.borderColor = '#38bdf8'
                    thirdAreaText.style.color = '#38bdf8'
                    splitLayout3_wthn()
                }
            }
        }
        if (fourthArea) {
            if (firstAreaAltInput.value !== "split") {
                if (fourthAreaAltInput.value == "comp" || fourthAreaAltInput.value == "cnvFrPDF") {
                    fourthAreaText.style.color = '#1e293b'
                } else {
                    fourthAreaText.style.color = '#6b7280'
                }
                fourthAreaInput.checked = false
                fourthColumnArea.style = null
            }  else {
                if (fourthAreaInput.checked == true) {
                    fourthColumnArea.style.borderColor = '#38bdf8'
                    fourthAreaText.style.color = '#38bdf8'
                    splitLayout3_cstm()
                }
            }
        }
        if (firstAreaAltInput.value == "split") {
            splitLayout2_split();
            if (thirdAreaInput.checked !== true && fourthAreaInput.checked !== true) {
                splitLayout2_splitClean();
            }
        } else if (firstAreaAltInput.value == "watermark") {
            wmLayout_ImageInputRestore()
            wmLayout_image()
        }
    }
}

if (secondArea) {
    secondArea.onclick = function() {
        secondColumnArea.style.borderColor = '#38bdf8'
        secondAreaText.style.color = '#38bdf8'
        secondAreaInput.checked = true
        if (firstAreaAltInput.value == "comp" || firstAreaAltInput.value == "cnvFrPDF") {
            firstAreaText.style.color = '#1e293b'
        } else {
            firstAreaText.style.color = '#6b7280'
        }
        firstColumnArea.style = null
        if (thirdArea) {
            if (thirdAreaAltInput.value == "comp" || thirdAreaAltInput.value == "cnvFrPDF") {
                thirdAreaText.style.color = '#1e293b'
            } else {
                thirdAreaText.style.color = '#6b7280'
            }
            thirdColumnArea.style = null
        }
        if (fourthArea) {
            if (fourthAreaAltInput.value == "comp" || fourthAreaAltInput.value == "cnvFrPDF") {
                fourthAreaText.style.color = '#1e293b'
            } else {
                fourthAreaText.style.color = '#6b7280'
            }
            fourthColumnArea.style = null
        }
        if (secondAreaAltInput.value == "split") {
            splitLayout2_delete();
        }  else if (firstAreaAltInput.value == "watermark") {
            wmLayout_TextInputRestore()
            wmLayout_text()
        }
    }
}

if (thirdArea) {
    thirdArea.onclick = function() {
        thirdColumnArea.style.borderColor = '#38bdf8'
        thirdAreaText.style.color = '#38bdf8'
        thirdAreaInput.checked = true
        if (firstAreaAltInput.value == "comp" || firstAreaAltInput.value == "cnvFrPDF") {
            firstAreaText.style.color = '#1e293b'
        } else {
            firstAreaText.style.color = '#6b7280'
        }
        firstColumnArea.style = null
        if (secondAreaAltInput.value == "comp" || secondAreaAltInput.value == "cnvFrPDF") {
            secondAreaText.style.color = '#1e293b'
        } else {
            secondAreaText.style.color = '#6b7280'
        }
        secondColumnArea.style = null
        if (fourthArea) {
            if (fourthAreaAltInput.value == "comp" || fourthAreaAltInput.value == "cnvFrPDF") {
                fourthAreaText.style.color = '#1e293b'
            } else {
                fourthAreaText.style.color = '#6b7280'
            }
            fourthColumnArea.style = null
        }
        if (thirdAreaAltInput.value == "split") {
            splitLayout3_wthn()
            if (firstAreaInput.checked == true) {
                firstColumnArea.style.borderColor = '#38bdf8'
                firstAreaText.style.color = '#38bdf8'
            }
        }
    }
}

if (fourthArea) {
    fourthArea.onclick = function() {
        fourthColumnArea.style.borderColor = '#38bdf8'
        fourthAreaText.style.color = '#38bdf8'
        fourthAreaInput.checked = true
        if (firstAreaAltInput.value == "comp" || firstAreaAltInput.value == "cnvFrPDF") {
            firstAreaText.style.color = '#1e293b'
        } else {
            firstAreaText.style.color = '#6b7280'
        }
        firstColumnArea.style = null
        if (secondAreaAltInput.value == "comp" || secondAreaAltInput.value == "cnvFrPDF") {
            secondAreaText.style.color = '#1e293b'
        } else {
            secondAreaText.style.color = '#6b7280'
        }
        secondColumnArea.style = null
        if (thirdArea) {
            if (thirdAreaAltInput.value == "comp" || thirdAreaAltInput.value == "cnvFrPDF") {
                thirdAreaText.style.color = '#1e293b'
            } else {
                thirdAreaText.style.color = '#6b7280'
            }
            thirdColumnArea.style = null
        }
        if (fourthAreaAltInput.value == "split") {
            splitLayout3_cstm()
            if (firstAreaInput.checked == true) {
                firstColumnArea.style.borderColor = '#38bdf8'
                firstAreaText.style.color = '#38bdf8'
            }
        }
    }
}

if (wmLayoutImageStyleAreaA) {
    wmLayoutImageStyleAreaA.onclick = function() {
        reuseOnClickWmLayoutImageStyleAreaA()
        if (firstAreaInput.checked == true) {
            firstColumnArea.style.borderColor = '#38bdf8'
            firstAreaText.style.color = '#38bdf8'
            firstAreaInput.checked = true
        } else if (secondAreaInput.checked == true) {
            secondColumnArea.style.borderColor = '#38bdf8'
            secondAreaText.style.color = '#38bdf8'
            secondAreaInput.checked = true
        }
    }
}

if (wmLayoutImageStyleAreaB) {
    wmLayoutImageStyleAreaB.onclick = function() {
        reuseOnClickWmLayoutImageStyleAreaB()
        if (firstAreaInput.checked == true) {
            firstColumnArea.style.borderColor = '#38bdf8'
            firstAreaText.style.color = '#38bdf8'
            firstAreaInput.checked = true
        } else if (secondAreaInput.checked == true) {
            secondColumnArea.style.borderColor = '#38bdf8'
            secondAreaText.style.color = '#38bdf8'
            secondAreaInput.checked = true
        }
    }
}

if (wmImageRotationAreaA) {
    wmImageRotationAreaA.onclick = function() {
        wmImageRotationColumnAreaA.style.borderColor = '#38bdf8'
        wmImageRotationRadioAreaTextA.style.color = '#38bdf8'
        wmImageRotationRadioAreaInputA.checked = true
        wmImageRotationColumnAreaB.style = null
        wmImageRotationRadioAreaTextB.style.color = '#6b7280'
        wmImageRotationColumnAreaC.style = null
        wmImageRotationRadioAreaTextC.style.color = '#6b7280'
        wmImageRotationColumnAreaD.style = null
        wmImageRotationRadioAreaTextD.style.color = '#6b7280'
        if (wmLayoutImageRadioAreaInputA.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaA()
        } else if (wmLayoutImageRadioAreaInputB.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaB()
        }
    }
}

if (wmImageRotationAreaB) {
    wmImageRotationAreaB.onclick = function() {
        wmImageRotationColumnAreaB.style.borderColor = '#38bdf8'
        wmImageRotationRadioAreaTextB.style.color = '#38bdf8'
        wmImageRotationRadioAreaInputB.checked = true
        wmImageRotationColumnAreaA.style = null
        wmImageRotationRadioAreaTextA.style.color = '#6b7280'
        wmImageRotationColumnAreaC.style = null
        wmImageRotationRadioAreaTextC.style.color = '#6b7280'
        wmImageRotationColumnAreaD.style = null
        wmImageRotationRadioAreaTextD.style.color = '#6b7280'
        if (wmLayoutImageRadioAreaInputA.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaA()
        } else if (wmLayoutImageRadioAreaInputB.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaB()
        }
    }
}

if (wmImageRotationAreaC) {
    wmImageRotationAreaC.onclick = function() {
        wmImageRotationColumnAreaC.style.borderColor = '#38bdf8'
        wmImageRotationRadioAreaTextC.style.color = '#38bdf8'
        wmImageRotationRadioAreaInputC.checked = true
        wmImageRotationColumnAreaA.style = null
        wmImageRotationRadioAreaTextA.style.color = '#6b7280'
        wmImageRotationColumnAreaB.style = null
        wmImageRotationRadioAreaTextB.style.color = '#6b7280'
        wmImageRotationColumnAreaD.style = null
        wmImageRotationRadioAreaTextD.style.color = '#6b7280'
        if (wmLayoutImageRadioAreaInputA.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaA()
        } else if (wmLayoutImageRadioAreaInputB.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaB()
        }
    }
}

if (wmImageRotationAreaD) {
    wmImageRotationAreaD.onclick = function() {
        wmImageRotationColumnAreaD.style.borderColor = '#38bdf8'
        wmImageRotationRadioAreaTextD.style.color = '#38bdf8'
        wmImageRotationRadioAreaInputD.checked = true
        wmImageRotationColumnAreaA.style = null
        wmImageRotationRadioAreaTextA.style.color = '#6b7280'
        wmImageRotationColumnAreaB.style = null
        wmImageRotationRadioAreaTextB.style.color = '#6b7280'
        wmImageRotationColumnAreaC.style = null
        wmImageRotationRadioAreaTextC.style.color = '#6b7280'
        if (wmLayoutImageRadioAreaInputA.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaA()
        } else if (wmLayoutImageRadioAreaInputB.checked == true) {
            reuseOnClickWmLayoutImageStyleAreaB()
        }
    }
}

if (wmChkFontFamilyA) {
    wmChkFontFamilyA.onclick = function() {
        wmColFontFamilyA.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextA.style.color = '#38bdf8'
        wmRadioFontFamilyA.checked = true
        wmColFontFamilyB.style = null
        wmRadioFontFamilyTextB.style.color = '#6b7280'
        wmColFontFamilyC.style = null
        wmRadioFontFamilyTextC.style.color = '#6b7280'
        wmColFontFamilyD.style = null
        wmRadioFontFamilyTextD.style.color = '#6b7280'
        wmColFontFamilyE.style = null
        wmRadioFontFamilyTextE.style.color = '#6b7280'
        wmColFontFamilyF.style = null
        wmRadioFontFamilyTextF.style.color = '#6b7280'
    }
}

if (wmChkFontFamilyB) {
    wmChkFontFamilyB.onclick = function() {
        wmColFontFamilyB.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextB.style.color = '#38bdf8'
        wmRadioFontFamilyB.checked = true
        wmColFontFamilyA.style = null
        wmRadioFontFamilyTextA.style.color = '#6b7280'
        wmColFontFamilyC.style = null
        wmRadioFontFamilyTextC.style.color = '#6b7280'
        wmColFontFamilyD.style = null
        wmRadioFontFamilyTextD.style.color = '#6b7280'
        wmColFontFamilyE.style = null
        wmRadioFontFamilyTextE.style.color = '#6b7280'
        wmColFontFamilyF.style = null
        wmRadioFontFamilyTextF.style.color = '#6b7280'
    }
}

if (wmChkFontFamilyC) {
    wmChkFontFamilyC.onclick = function() {
        wmColFontFamilyC.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextC.style.color = '#38bdf8'
        wmRadioFontFamilyC.checked = true
        wmColFontFamilyB.style = null
        wmRadioFontFamilyTextB.style.color = '#6b7280'
        wmColFontFamilyA.style = null
        wmRadioFontFamilyTextA.style.color = '#6b7280'
        wmColFontFamilyD.style = null
        wmRadioFontFamilyTextD.style.color = '#6b7280'
        wmColFontFamilyE.style = null
        wmRadioFontFamilyTextE.style.color = '#6b7280'
        wmColFontFamilyF.style = null
        wmRadioFontFamilyTextF.style.color = '#6b7280'
    }
}

if (wmChkFontFamilyD) {
    wmChkFontFamilyD.onclick = function() {
        wmColFontFamilyD.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextD.style.color = '#38bdf8'
        wmRadioFontFamilyD.checked = true
        wmColFontFamilyB.style = null
        wmRadioFontFamilyTextB.style.color = '#6b7280'
        wmColFontFamilyC.style = null
        wmRadioFontFamilyTextC.style.color = '#6b7280'
        wmColFontFamilyA.style = null
        wmRadioFontFamilyTextA.style.color = '#6b7280'
        wmColFontFamilyE.style = null
        wmRadioFontFamilyTextE.style.color = '#6b7280'
        wmColFontFamilyF.style = null
        wmRadioFontFamilyTextF.style.color = '#6b7280'
    }
}

if (wmChkFontFamilyE) {
    wmChkFontFamilyE.onclick = function() {
        wmColFontFamilyE.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextE.style.color = '#38bdf8'
        wmRadioFontFamilyE.checked = true
        wmColFontFamilyB.style = null
        wmRadioFontFamilyTextB.style.color = '#6b7280'
        wmColFontFamilyC.style = null
        wmRadioFontFamilyTextC.style.color = '#6b7280'
        wmColFontFamilyD.style = null
        wmRadioFontFamilyTextD.style.color = '#6b7280'
        wmColFontFamilyA.style = null
        wmRadioFontFamilyTextA.style.color = '#6b7280'
        wmColFontFamilyF.style = null
        wmRadioFontFamilyTextF.style.color = '#6b7280'
    }
}

if (wmChkFontFamilyF) {
    wmChkFontFamilyF.onclick = function() {
        wmColFontFamilyF.style.borderColor = '#38bdf8'
        wmRadioFontFamilyTextF.style.color = '#38bdf8'
        wmRadioFontFamilyF.checked = true
        wmColFontFamilyB.style = null
        wmRadioFontFamilyTextB.style.color = '#6b7280'
        wmColFontFamilyC.style = null
        wmRadioFontFamilyTextC.style.color = '#6b7280'
        wmColFontFamilyD.style = null
        wmRadioFontFamilyTextD.style.color = '#6b7280'
        wmColFontFamilyE.style = null
        wmRadioFontFamilyTextE.style.color = '#6b7280'
        wmColFontFamilyA.style = null
        wmRadioFontFamilyTextA.style.color = '#6b7280'
    }
}

if (wmChkFontStyleA) {
    wmChkFontStyleA.onclick = function() {
        wmColFontStyleA.style.borderColor = '#38bdf8'
        wmRadioFontStyleTextA.style.color = '#38bdf8'
        wmRadioFontStyleA.checked = true
        wmColFontStyleB.style = null
        wmRadioFontStyleTextB.style.color = '#6b7280'
        wmColFontStyleC.style = null
        wmRadioFontStyleTextC.style.color = '#6b7280'
    }
}

if (wmChkFontStyleB) {
    wmChkFontStyleB.onclick = function() {
        wmColFontStyleB.style.borderColor = '#38bdf8'
        wmRadioFontStyleTextB.style.color = '#38bdf8'
        wmRadioFontStyleB.checked = true
        wmColFontStyleA.style = null
        wmRadioFontStyleTextA.style.color = '#6b7280'
        wmColFontStyleC.style = null
        wmRadioFontStyleTextC.style.color = '#6b7280'
    }
}

if (wmChkFontStyleC) {
    wmChkFontStyleC.onclick = function() {
        wmColFontStyleC.style.borderColor = '#38bdf8'
        wmRadioFontStyleTextC.style.color = '#38bdf8'
        wmRadioFontStyleC.checked = true
        wmColFontStyleB.style = null
        wmRadioFontStyleTextB.style.color = '#6b7280'
        wmColFontStyleA.style = null
        wmRadioFontStyleTextA.style.color = '#6b7280'
    }
}

if (wmChkLayoutStyleA) {
    wmChkLayoutStyleA.onclick = function() {
        wmColLayoutStyleA.style.borderColor = '#38bdf8'
        wmRadioLayoutStyleTextA.style.color = '#38bdf8'
        wmRadioLayoutStyleA.checked = true
        wmColLayoutStyleB.style = null
        wmRadioLayoutStyleTextB.style.color = '#6b7280'

    }
}

if (wmChkLayoutStyleB) {
    wmChkLayoutStyleB.onclick = function() {

        wmColLayoutStyleB.style.borderColor = '#38bdf8'
        wmRadioLayoutStyleTextB.style.color = '#38bdf8'
        wmRadioLayoutStyleB.checked = true
        wmColLayoutStyleA.style = null
        wmRadioLayoutStyleTextA.style.color = '#6b7280'
    }
}

if (wmChkRotationA) {
    wmChkRotationA.onclick = function() {
        wmColRotationA.style.borderColor = '#38bdf8'
        wmRadioRotationTextA.style.color = '#38bdf8'
        wmRadioRotationA.checked = true
        wmColRotationB.style = null
        wmRadioRotationTextB.style.color = '#6b7280'
        wmColRotationC.style = null
        wmRadioRotationTextC.style.color = '#6b7280'
        wmColRotationD.style = null
        wmRadioRotationTextD.style.color = '#6b7280'
    }
}

if (wmChkRotationB) {
    wmChkRotationB.onclick = function() {
        wmColRotationB.style.borderColor = '#38bdf8'
        wmRadioRotationTextB.style.color = '#38bdf8'
        wmRadioRotationB.checked = true
        wmColRotationA.style = null
        wmRadioRotationTextA.style.color = '#6b7280'
        wmColRotationC.style = null
        wmRadioRotationTextC.style.color = '#6b7280'
        wmColRotationD.style = null
        wmRadioRotationTextD.style.color = '#6b7280'
    }
}

if (wmChkRotationC) {
    wmChkRotationC.onclick = function() {
        wmColRotationC.style.borderColor = '#38bdf8'
        wmRadioRotationTextC.style.color = '#38bdf8'
        wmRadioRotationC.checked = true
        wmColRotationB.style = null
        wmRadioRotationTextB.style.color = '#6b7280'
        wmColRotationA.style = null
        wmRadioRotationTextA.style.color = '#6b7280'
        wmColRotationD.style = null
        wmRadioRotationTextD.style.color = '#6b7280'
    }
}

if (wmChkRotationD) {
    wmChkRotationD.onclick = function() {
        wmColRotationD.style.borderColor = '#38bdf8'
        wmRadioRotationTextD.style.color = '#38bdf8'
        wmRadioRotationD.checked = true
        wmColRotationB.style = null
        wmRadioRotationTextB.style.color = '#6b7280'
        wmColRotationC.style = null
        wmRadioRotationTextC.style.color = '#6b7280'
        wmColRotationA.style = null
        wmRadioRotationTextA.style.color = '#6b7280'
    }
}

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
    if (validation == 'comp') {
        document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
        document.getElementById("submitBtn_1").style.color = "white"
    }
    if (validation == 'cnvFrPDF') {
        document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
        document.getElementById("submitBtn_1").style.color = "white"
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

function dropdownManage() {
    if (document.getElementById('dropdownNavbarLink').value == "1") {
        document.getElementById('dropdownNavbarImage').style.transform = 'rotate(-90deg)';
        document.getElementById('dropdownNavbarLink').value = "0";
    } else {
        document.getElementById('dropdownNavbarImage').style.transform = 'rotate(0deg)';
        document.getElementById('dropdownNavbarLink').value = "1";

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
    } else if (document.getElementById("wmColImageLayoutStyleA") !== null) {
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

function reuseOnClickWmLayoutImageStyleAreaA() {
    wmLayoutImageStyleColumnAreaA.style.borderColor = '#38bdf8'
    wmLayoutImageStyleAreaTextA.style.color = '#38bdf8'
    wmLayoutImageRadioAreaInputA.checked = true
    firstColumnArea.style = null
    firstAreaText.color = '#6b7280'
    secondColumnArea.style = null
    secondAreaText.color = '#6b7280'
    wmLayoutImageStyleColumnAreaB.style = null
    wmLayoutImageStyleAreaTextB.style.color = '#6b7280'
    if (firstAreaInput.checked == true) {
        firstColumnArea.style.borderColor = '#38bdf8'
        firstAreaText.style.color = '#38bdf8'
        firstAreaInput.checked = true
    } else if (secondAreaInput.checked == true) {
        secondColumnArea.style.borderColor = '#38bdf8'
        secondAreaText.style.color = '#38bdf8'
        secondAreaInput.checked = true
    }
}

function reuseOnClickWmLayoutImageStyleAreaB() {
    wmLayoutImageStyleColumnAreaB.style.borderColor = '#38bdf8'
    wmLayoutImageStyleAreaTextB.style.color = '#38bdf8'
    wmLayoutImageRadioAreaInputB.checked = true
    firstColumnArea.style = null
    firstAreaText.color = '#6b7280'
    secondColumnArea.style = null
    secondAreaText.color = '#6b7280'
    wmLayoutImageStyleColumnAreaA.style = null
    wmLayoutImageStyleAreaTextA.style.color = '#6b7280'
    if (firstAreaInput.checked == true) {
        firstColumnArea.style.borderColor = '#38bdf8'
        firstAreaText.style.color = '#38bdf8'
        firstAreaInput.checked = true
    } else if (secondAreaInput.checked == true) {
        secondColumnArea.style.borderColor = '#38bdf8'
        secondAreaText.style.color = '#38bdf8'
        secondAreaInput.checked = true
    }
}

function showVal(newVal){
    document.getElementById("TransparencyValue").innerHTML=newVal+" %";
}

function splitLayout2_split(){
    document.getElementById("splitLayout2_split").style.display = null;
    document.getElementById("splitLayout2_delete").style.display = "none";
    document.getElementById("submitBtn_2").style.display= "none";
    document.getElementById("submitBtn_3").style.display= "none";
};

function splitLayout2_splitClean() {
    document.getElementById("splitLayout3Cstm").style.display = "none";
    document.getElementById("splitLayout3Wthn").style.display = "none";
    document.getElementById("submitBtn_2").style.display = "none";
    document.getElementById("submitBtn_3").style.display= "none";
};

function splitLayout2_delete(){
    document.getElementById("splitLayout2_split").style.display = "none";
    document.getElementById("splitLayout2_delete").style.display = null;
    document.getElementById("splitLayout3Cstm").style.display = "none";
    document.getElementById("splitLayout3Wthn").style.display = "none";
    document.getElementById("submitBtn_2").style.display = "none";
    document.getElementById("submitBtn_3").style.display = null;
};

function splitLayout3_cstm(){
    document.getElementById("splitLayout3Cstm").style.display = null;
    document.getElementById("splitLayout3Wthn").style.display = "none";
    document.getElementById("submitBtn_2").style.display = null;
    document.getElementById("submitBtn_3").style.display = "none";
};

function splitLayout3_wthn(){
    document.getElementById("splitLayout3Cstm").style.display = "none";
    document.getElementById("splitLayout3Wthn").style.display = null;
    document.getElementById("submitBtn_2").style.display= null;
    document.getElementById("submitBtn_3").style.display= "none";
};

function wmLayout_image(){
    document.getElementById("wmLayoutImage").style.display = null
    document.getElementById("wmLayoutText").style.display = "none"
};

function wmLayout_ImageInputRestore() {
    if (wmLayoutImageStyleColumnAreaA.style.borderColor == "rgb(56, 189, 248)") {
        wmLayoutImageRadioAreaInputA.checked = true
    }
    if (wmLayoutImageStyleColumnAreaB.style.borderColor == "rgb(56, 189, 248)") {
        wmLayoutImageRadioAreaInputB.checked = true
    }
    if (wmImageRotationColumnAreaA.style.borderColor == "rgb(56, 189, 248)") {
        wmImageRotationRadioAreaInputA.checked = true
    }
    if (wmImageRotationColumnAreaB.style.borderColor == "rgb(56, 189, 248)") {
        wmImageRotationRadioAreaInputB.checked = true
    }
    if (wmImageRotationColumnAreaC.style.borderColor == "rgb(56, 189, 248)") {
        wmImageRotationRadioAreaInputC.checked = true
    }
    if (wmImageRotationColumnAreaD.style.borderColor == "rgb(56, 189, 248)") {
        wmImageRotationRadioAreaInputD.checked = true
    }
}

function wmLayout_text(){
    document.getElementById("wmLayoutImage").style.display = "none"
    document.getElementById("wmLayoutText").style.display = null
}

function wmLayout_TextInputRestore() {
    if (wmColLayoutStyleA.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioLayoutStyleA.checked = true
    }
    if (wmColLayoutStyleB.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioLayoutStyleB.checked = true
    }
    if (wmColRotationA.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioRotationA.checked = true
    }
    if (wmColRotationB.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioRotationB.checked = true
    }
    if (wmColRotationC.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioRotationC.checked = true
    }
    if (wmColRotationD.style.borderColor == "rgb(56, 189, 248)") {
        wmRadioRotationD.checked = true
    }
}

/*
function remove_wm() {
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    pdfComp.style.display="none";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}
*/

init();
