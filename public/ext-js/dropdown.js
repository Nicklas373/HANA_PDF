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
