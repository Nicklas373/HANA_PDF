import { Modal } from 'flowbite';

const $targetEl = document.getElementById('loadingModal');
const $newModal = document.getElementById('errModal');
const options = {
    placement: 'bottom-right',
    backdrop: 'dynamic',
    backdropClasses: 'bg-gray-900 bg-opacity-50 backdrop-filter backdrop-blur-sm fixed inset-0 z-40',
    closable: true,
    onHide: () => {
        //console.log('modal is hidden');
    },
    onShow: () => {
        //console.log('modal is shown');
    },
    onToggle: () => {
        //console.log('modal has been toggled');
    }
};
const modal = new Modal($targetEl, options);
const newModal = new Modal($newModal, options);
let uploadBtn = false;
let uploadBtn_submit = document.getElementById('submitBtn');
var procTitleMessageModal = document.getElementById("titleMessageModal");
var errAltSubMessageModal = document.getElementById("altSubMessageModal");
var errMessage = document.getElementById("errMessageModal");
var errSubMessage = document.getElementById("errSubMessageModal");
var errListMessage = document.getElementById("err-list");
var errListTitleMessage = document.getElementById("err-list-title");
var procBtn_submit = document.getElementById('submitBtn_1');
var procBtn2_submit = document.getElementById('submitBtn_2');
var procBtn3_submit = document.getElementById('submitBtn_3');

if (uploadBtn_submit) {
    uploadBtn_submit.onclick = function(event) {
        uploadBtn = true;
        submit(event)
    }
}

if (procBtn_submit) {
    procBtn_submit.onclick = function(event) {
        uploadBtn = false;
        submit(event)
    }
}

if (procBtn2_submit) {
    procBtn2_submit.onclick = function(event) {
        uploadBtn = false;
        submit(event)
    }
}

if (procBtn3_submit) {
    procBtn3_submit.onclick = function(event) {
        uploadBtn = false;
        submit(event)
    }
}

function submit(event) {
    if (document.getElementById("filelist") !== null) {
        var input = document.getElementById('multiple_files').files;
        var extErr = false;
        var falseCount = 0;
        var trueCount = 0;

       if (document.getElementById("multiple_files").value == '' && document.getElementById("fileAlt") != null && uploadBtn == false) {
            procTitleMessageModal.innerText = "Processing PDF..."
            errMessage.style.visibility = null;
            errSubMessage.style.visibility = null;
            errAltSubMessageModal.style.display = "none";
            newModal.hide();
            modal.show();
        } else if (document.getElementById("multiple_files").value != '' && document.getElementById("fileAlt") != null && uploadBtn == true ||
            document.getElementById("multiple_files").value != '' && document.getElementById("fileAlt") == null && uploadBtn == true) {
            for(var i=0;i<input.length;i++){
                var arrayFile = input[i];
                let multiFileSize = arrayFile.size;
                if (arrayFile.type == "application/pdf")
                {
                    if (multiFileSize >= 26214400) {
                        falseCount++;
                    } else {
                        trueCount++;
                    }
                } else {
                    falseCount++;
                    extErr = true;
                }
            }
            if (falseCount > 0) {
                if (extErr) {
                    event.preventDefault();
                    errMessage.innerText  = "Unsupported file format!";
                    errSubMessage.innerText = "";
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: PDF");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Uploaded file has exceeds the limit!";
                    errSubMessage.innerText = ""
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Maximum file size 25 MB");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            } else {
                procTitleMessageModal.innerText = "Uploading PDF..."
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.hide();
                modal.show();
            }
        } else if (document.getElementById("multiple_files").value == '' && document.getElementById("fileAlt") == null && uploadBtn == true ||
                    document.getElementById("multiple_files").value == '' && document.getElementById("fileAlt") != null && uploadBtn == true) {
                        event.preventDefault();
                        errMessage.innerText  = "Please choose PDF file!";
                        errSubMessage.innerText = ""
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.show();
        } else {
            event.preventDefault();
            errMessage.innerText  = "Index out of bound!";
            errSubMessage.innerText = "";
            errListTitleMessage.innerText = "Error message"
            resetErrListMessage();
            generateMesssage("Merge decision logic error");
            errAltSubMessageModal.style = null;
            newModal.show();
        }
    }
    if (document.getElementById("file_input") !== null) {
        if (document.getElementById('cnvFrPDF') !== null || document.getElementById('compPDF') !== null) {
            if (!document.getElementById("file_input").value && document.getElementById("fileAlt") == null && uploadBtn == true ||
                !document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == true) {
                    event.preventDefault();
                    errMessage.innerText  = "Please choose PDF file!";
                    errSubMessage.innerText = ""
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.show();
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                if (document.getElementById('compPDF') !== null) {
                    if (!document.getElementById('firstRadio').checked && !document.getElementById('secondRadio').checked && !document.getElementById('thirdRadio').checked) {
                        var compLow = document.getElementById('firstRadio')
                        var compMed = document.getElementById('secondRadio')
                        var compHigh = document.getElementById('thirdRadio')
                        event.preventDefault();
                        errMessage.innerText  = "Please fill out these fields!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerText = "Required fields:"
                        errAltSubMessageModal.style = null;
                        resetErrListMessage();
                        generateMesssage("Compression Quality");
                        compLow.style.borderColor = '#dc2626'
                        compMed.style.borderColor = '#dc2626'
                        compHigh.style.borderColor = '#dc2626'
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerText = "Processing PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else  if (document.getElementById('cnvFrPDF') !== null) {
                    if (!document.getElementById('firstRadio').checked && !document.getElementById('secondRadio').checked && !document.getElementById('thirdRadio').checked && !document.getElementById('fourthRadio').checked) {
                        var cnvToImg = document.getElementById('firstRadio')
                        var cnvToPPTX = document.getElementById('secondRadio')
                        var cnvToXLSX = document.getElementById('thirdRadio')
                        var cnvToDOCX = document.getElementById('fourthRadio')
                        event.preventDefault();
                        errMessage.innerText  = "Please fill out these fields!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerText = "Required fields:"
                        errAltSubMessageModal.style = null;
                        resetErrListMessage();
                        generateMesssage("Document Format");
                        cnvToImg.style.borderColor = '#dc2626'
                        cnvToPPTX.style.borderColor = '#dc2626'
                        cnvToXLSX.style.borderColor = '#dc2626'
                        cnvToDOCX.style.borderColor = '#dc2626'
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerText = "Processing PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    procTitleMessageModal.innerText = "Processing PDF..."
                    errMessage.style.visibility = null;
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.hide();
                    modal.show();
                }
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 26214400) {
                        event.preventDefault();
                        errMessage.innerText  = "Uploaded file has exceeds the limit!";
                        errSubMessage.innerText = ""
                        errListTitleMessage.innerText = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerText = "Uploading PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Unsupported file format!";
                    errSubMessage.innerText = "";
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: PDF");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            }
        } else if (document.getElementById('cnvToPDF') !== null) {
            if (!document.getElementById("file_input").value && document.getElementById("fileAlt") == null && uploadBtn == true ||
                !document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == true) {
                    event.preventDefault();
                    errMessage.innerText  = "Please choose document file!";
                    errSubMessage.innerText = ""
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.show();
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                procTitleMessageModal.innerText = "Processing Document..."
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/vnd.openxmlformats-officedocument.wordprocessingml.document" ||
                    file.files[0].type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
                    file.files[0].type == "application/vnd.openxmlformats-officedocument.presentationml.presentation" ||
                    file.files[0].type == "image/jpg" ||
                    file.files[0].type == "image/jpeg" ||
                    file.files[0].type == "image/png" ||
                    file.files[0].type == "image/tif" ||
                    file.files[0].type == "image/tiff")
                {
                    if (fileSize >= 26214400) {
                        event.preventDefault();
                        errMessage.innerText  = "Uploaded file has exceeds the limit!";
                        errSubMessage.innerText = ""
                        errListTitleMessage.innerText = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerText = "Upload Document"
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Unsupported file format!";
                    errSubMessage.innerText = "";
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: DOCX, XLSX, PPTX");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            }
        } else if (document.getElementById('splitLayout1')) {
            if (!document.getElementById("file_input").value && uploadBtn == false) {
                if (document.getElementById("firstRadio").checked) {
                    let cusPage = false;
                    let fromPage = false;
                    let toPage = false;
                    var customPage = document.getElementById('customPageSplit')
                    var firstPage = document.getElementById('fromPage')
                    var lastPage = document.getElementById('toPage')
                    var totalPage = document.getElementById('totalPage')
                    if (document.getElementById("firstRadio").value == "split") {
                        if (document.getElementById("splitRadio")) {
                           if (document.getElementById("thirdRadio").checked) {
                                if (document.getElementById("thirdRadio").value == "selPages") {
                                    if (document.getElementById("fromPage").value) {
                                        fromPage = true;
                                    } else {
                                        fromPage = false;
                                    }
                                    if (document.getElementById("toPage").value) {
                                        toPage = true;
                                    } else {
                                        toPage = false;
                                    }
                                    if (fromPage && toPage) {
                                        if (document.getElementById("fromPage").value.charAt(0) == "-") {
                                            event.preventDefault();
                                            errMessage.innerText  = "Invalid page number range!";
                                            errListTitleMessage.innerText = "Error message"
                                            errAltSubMessageModal.style = null;
                                            resetErrListMessage();
                                            generateMesssage("Page number can't use negative number");
                                            firstPage.style.borderColor = '#dc2626'
                                            newModal.show();
                                        } else if (document.getElementById("toPage").value.charAt(0) == "-") {
                                            event.preventDefault();
                                            errMessage.innerText  = "Invalid page number range!";
                                            errListTitleMessage.innerText = "Error message"
                                            errAltSubMessageModal.style = null;
                                            resetErrListMessage();
                                            generateMesssage("Page number can't use negative number");
                                            lastPage.style.borderColor = '#dc2626'
                                            newModal.show();
                                        } else if (parseInt(document.getElementById("fromPage").value) >= parseInt(document.getElementById("toPage").value)) {
                                            event.preventDefault();
                                            errMessage.innerText  = "Invalid page number range!";
                                            errListTitleMessage.innerText = "Error message"
                                            errAltSubMessageModal.style = null;
                                            resetErrListMessage();
                                            generateMesssage("First page can't be more than last page");
                                            generateMesssage("First page can't have same value with last page");
                                            firstPage.style.borderColor = '#dc2626'
                                            newModal.show();
                                        } else if (parseInt(document.getElementById("toPage").value) >= parseInt(totalPage.value)) {
                                            event.preventDefault();
                                            errMessage.innerText  = "Invalid page number range!";
                                            errListTitleMessage.innerText = "Error message"
                                            errAltSubMessageModal.style = null;
                                            resetErrListMessage();
                                            generateMesssage("Last page can't be more than total page ("+totalPage.value+")");
                                            generateMesssage("Last page can't have same value with total page ("+totalPage.value+")");
                                            lastPage.style.borderColor = '#dc2626'
                                            newModal.show();
                                        } else {
                                            procTitleMessageModal.innerText = "Processing PDF..."
                                            errMessage.style.visibility = null;
                                            errSubMessage.style.visibility = null;
                                            errAltSubMessageModal.style.display = "none";
                                            newModal.hide();
                                            modal.show();
                                        }
                                    } else if (!fromPage && !toPage) {
                                        event.preventDefault();
                                        errMessage.innerText  = "Please fill out these fields!";
                                        errSubMessage.innerText = "";
                                        errListTitleMessage.innerText = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("First Pages");
                                        generateMesssage("Last Pages");
                                        firstPage.style.borderColor = '#dc2626'
                                        lastPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else if (!fromPage && toPage) {
                                        event.preventDefault();
                                        errMessage.innerText  = "Please fill out these fields!";
                                        errSubMessage.innerText = "";
                                        errListTitleMessage.innerText = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("First Pages");
                                        firstPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else if (fromPage && !toPage) {
                                        event.preventDefault();
                                        errMessage.innerText  = "Please fill out these fields!";
                                        errSubMessage.innerText = "";
                                        errListTitleMessage.innerText = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("Last Pages");
                                        lastPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else {
                                        procTitleMessageModal.innerText = "Processing PDF..."
                                        errMessage.style = null;
                                        errSubMessage.style = null;
                                        errAltSubMessageModal.style.display = "none";
                                        newModal.hide();
                                        modal.show();
                                    }
                                } else {
                                    event.preventDefault();
                                    errMessage.innerText  = "Index out of bound!";
                                    errSubMessage.innerText = "";
                                    errAltSubMessageModal.style = null;
                                    errListTitleMessage.innerText = "Error message"
                                    resetErrListMessage();
                                    generateMesssage("Split selected page logic error");
                                    errAltSubMessageModal.style = null;
                                    newModal.show();
                                }
                            } else if (document.getElementById("fourthRadio").checked) {
                                if (document.getElementById("fourthRadio").value == "cusPages") {
                                    if (document.getElementById("customPageSplit").value) {
                                         cusPage = true;
                                    } else {
                                         cusPage = false;
                                    }
                                    if (cusPage) {
                                        procTitleMessageModal.innerText = "Processing PDF..."
                                         errMessage.style.visibility = null;
                                         errSubMessage.style.visibility = null;
                                         errAltSubMessageModal.style.display = "none";
                                         newModal.hide();
                                         modal.show();
                                    } else {
                                        event.preventDefault();
                                        errMessage.innerText  = "Please fill out these fields!";
                                        errSubMessage.innerText = "";
                                        errListTitleMessage.innerText = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("Custom Pages");
                                        customPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    }
                                } else {
                                    event.preventDefault();
                                    errMessage.innerText  = "Index out of bound!";
                                    errSubMessage.innerText = "";
                                    errListTitleMessage.innerText = "Error message"
                                    resetErrListMessage();
                                    generateMesssage("Split custom page logic error");
                                    errAltSubMessageModal.style = null;
                                    newModal.show();
                                }
                             } else {
                                event.preventDefault();
                                errMessage.innerText  = "Index out of bound!";
                                errSubMessage.innerText = "";
                                errListTitleMessage.innerText = "Error message"
                                resetErrListMessage();
                                generateMesssage("Cannot define selected or custom page");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerText  = "Kaori";
                            errSubMessage.style.visibility = null;
                            errAltSubMessageModal.style.display = "none";
                            newModal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerText  = "Index out of bound!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerinnerTextHTML = "Error message"
                        resetErrListMessage();
                        generateMesssage("Split options decision logic error");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    }
                } else if (document.getElementById("secondRadio").checked) {
                    let cusPage = false;
                    var customPage = document.getElementById('customPageDelete')
                    if (document.getElementById("secondRadio").value == "delete") {
                            if (document.getElementById("customPageDelete").value) {
                                 cusPage = true;
                            } else {
                                 cusPage = false;
                            }
                            if (cusPage) {
                                procTitleMessageModal.innerText = "Processing PDF..."
                                errMessage.style.visibility = null;
                                errSubMessage.style.visibility = null;
                                errAltSubMessageModal.style.display = "none";
                                newModal.hide();
                                modal.show();
                            } else {
                                event.preventDefault();
                                errMessage.innerText  = "Please fill out these fields!";
                                errSubMessage.innerText = "";
                                errListTitleMessage.innerText = "Required fields:"
                                errAltSubMessageModal.style = null;
                                resetErrListMessage();
                                generateMesssage("Custom Pages");
                                errSubMessage.style.visibility = null;
                                customPage.style.borderColor = '#dc2626'
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerText  = "Index out of bound!";
                            errSubMessage.innerText = "";
                            errListTitleMessage.innerText = "Error message"
                            resetErrListMessage();
                            generateMesssage("Delete options decision logic error");
                            errAltSubMessageModal.style = null;
                            newModal.show();
                        }
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Index out of bound!";
                    errSubMessage.innerText = "";
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Split decision logic error");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            } else if (document.getElementById("file_input").value && uploadBtn == true) {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 26214400) {
                        event.preventDefault();
                        errMessage.innerText  = "Uploaded file has exceeds the limit!";
                        errSubMessage.innerText = ""
                        errListTitleMessage.innerText = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerText = "Uploading PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Unsupported file format!";
                    errSubMessage.innerText = "";
                    errListTitleMessage.innerText = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: PDF");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            } else {
                event.preventDefault();
                errMessage.innerText  = "Please choose PDF file!";
                errSubMessage.innerText = ""
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.show();
            }
        } else if (document.getElementById('wmColImageLayoutStyleA')) {
            var wmImageSwitcher = document.getElementById("wmTypeImage");
            var wmTextSwitcher = document.getElementById("wmTypeText");
            if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                if (document.getElementById('firstRadio').checked == true) {
                    var wmImage = document.getElementById("wm_file_input");
                    wmImageSwitcher.checked = true;
                    wmTextSwitcher.checked = false;
                    if (document.getElementById("wm_file_input").value) {
                        var imgFile = document.getElementById("wm_file_input");
                        let fileSize = imgFile.files[0].size;
                        if (imgFile.files[0].type == "image/jpeg" || imgFile.files[0].type == "image/png"
                            || imgFile.files[0].type == "image/jpg") {
                            if (fileSize >= 5242880) {
                                event.preventDefault();
                                errMessage.innerText  = "Uploaded file has exceeds the limit!";
                                errSubMessage.innerText = ""
                                errListTitleMessage.innerText = "Error message"
                                resetErrListMessage();
                                generateMesssage("Maximum file size 5 MB");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            } else {
                                if (document.getElementById('watermarkPageImage').value) {
                                    procTitleMessageModal.innerText = "Processing PDF..."
                                    errMessage.style.visibility = null;
                                    errSubMessage.style.visibility = null;
                                    errAltSubMessageModal.style.display = "none";
                                    newModal.hide();
                                    modal.show();
                                } else {
                                    var wmPage = document.getElementById("watermarkPageImage");
                                    event.preventDefault();
                                    errMessage.innerText  = "Please fill out these fields!";
                                    errSubMessage.innerText = "";
                                    errListTitleMessage.innerText = "Required fields:"
                                    resetErrListMessage();
                                    generateMesssage("Pages");
                                    errAltSubMessageModal.style = null;
                                    wmPage.style.borderColor = '#dc2626'
                                    newModal.show();
                                }
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerText  = "Unsupported file format!";
                            errSubMessage.innerText = "";
                            errListTitleMessage.innerText = "Error message"
                            resetErrListMessage();
                            generateMesssage("Supported file format: JPG, PNG");
                            errAltSubMessageModal.style = null;
                            newModal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerText  = "Please fill out these fields!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerText = "Required fields:"
                        resetErrListMessage();
                        generateMesssage("Image");
                        errAltSubMessageModal.style = null;
                        wmImage.style.borderColor = '#dc2626'
                        newModal.show();
                    }
                } else if (document.getElementById('secondRadio').checked == true) {
                    var wmText = document.getElementById("watermarkText");
                    wmImageSwitcher.checked = false;
                    wmTextSwitcher.checked = true;
                    if (!document.getElementById('watermarkText').value && !document.getElementById('watermarkPageText').value) {
                        var wmPage = document.getElementById("watermarkPageText");
                        event.preventDefault();
                        errMessage.innerText  = "Please fill out these fields!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerText = "Required fields:"
                        resetErrListMessage();
                        generateMesssage("Pages");
                        generateMesssage("Text");
                        errAltSubMessageModal.style = null;
                        wmText.style.borderColor = '#dc2626'
                        wmPage.style.borderColor = '#dc2626'
                        newModal.show();
                    } else if (document.getElementById('watermarkText').value) {
                        if (document.getElementById('watermarkPageText').value) {
                            errMessage.style.visibility = null;
                            procTitleMessageModal.innerText = "Processing PDF..."
                            errSubMessage.style.visibility = null;
                            errAltSubMessageModal.style.display = "none";
                            newModal.hide();
                            modal.show();
                        } else {
                            var wmPage = document.getElementById("watermarkPageText");
                            event.preventDefault();
                            errMessage.innerText  = "Please fill out these fields!";
                            errSubMessage.innerText = "";
                            errListTitleMessage.innerText = "Required fields:"
                            resetErrListMessage();
                            generateMesssage("Pages");
                            errAltSubMessageModal.style = null;
                            wmPage.style.borderColor = '#dc2626'
                            newModal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerText  = "Please fill out these fields!";
                        errSubMessage.innerText = "";
                        errListTitleMessage.innerText = "Required fields:"
                        resetErrListMessage();
                        generateMesssage("Text");
                        errAltSubMessageModal.style = null;
                        wmText.style.borderColor = '#dc2626'
                        newModal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerText  = "Please choose watermark options!";
                    errSubMessage.innerText = ""
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.show();
                }
            } else if (document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == true ||
                       document.getElementById("file_input").value && document.getElementById("fileAlt") == null && uploadBtn == true) {
                        var file = document.getElementById("file_input");
                        let fileSize = file.files[0].size;
                        if (file.files[0].type == "application/pdf")
                        {
                            if (fileSize >= 26214400) {
                                event.preventDefault();
                                errMessage.innerText  = "Uploaded file has exceeds the limit!";
                                errSubMessage.innerText = ""
                                errListTitleMessage.innerText = "Error message"
                                resetErrListMessage();
                                generateMesssage("Maximum file size 25 MB");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            } else {
                                procTitleMessageModal.innerText = "Uploading PDF..."
                                errMessage.style.visibility = null;
                                errSubMessage.style.visibility = null;
                                errAltSubMessageModal.style.display = "none";
                                newModal.hide();
                                modal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerText  = "Unsupported file format!";
                            errSubMessage.innerText = "";
                            errListTitleMessage.innerText = "Error message"
                            resetErrListMessage();
                            generateMesssage("Supported file format: PDF");
                            errAltSubMessageModal.style = null;
                            newModal.show();
                        }
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == true ||
                        !document.getElementById("file_input").value && document.getElementById("fileAlt") == null && uploadBtn == true) {
                            event.preventDefault();
                            errMessage.innerText  = "Please choose PDF file!";
                            errSubMessage.innerText = ""
                            errSubMessage.style.visibility = null;
                            errAltSubMessageModal.style.display = "none";
                            newModal.show();
            } else {
                event.preventDefault();
                errMessage.innerText  = "Index out of bound!";
                errSubMessage.innerText = "";
                errListTitleMessage.innerText = "Error message"
                resetErrListMessage();
                generateMesssage("Watermark decision logic error");
                errAltSubMessageModal.style = null;
                newModal.show();
            }
        } else {
            event.preventDefault();
            errMessage.innerText  = "Index out of bound!";
            errSubMessage.innerText = "";
            errListTitleMessage.innerText = "Error message"
            resetErrListMessage();
            generateMesssage("PDF decision logic error");
            errAltSubMessageModal.style = null;
            newModal.show();
        }
    }
    if (document.getElementById('urlToPDF') !== null) {
        var urlAddr = document.getElementById('urlToPDF')
        if (document.getElementById('urlToPDF').value) {
            procTitleMessageModal.innerText = "Processing URL..."
            errMessage.style.visibility = null;
            errSubMessage.style.visibility = null;
            errAltSubMessageModal.style.display = "none";
            newModal.hide();
            modal.show();
        } else {
            event.preventDefault();
            errMessage.innerText  = "Please fill out these fields!";
            errSubMessage.innerText = "";
            errListTitleMessage.innerText = "Required fields:"
            resetErrListMessage();
            generateMesssage("URL Address");
            errAltSubMessageModal.style = null;
            urlAddr.style.borderColor = '#dc2626'
            newModal.show();
        }
    }
}

function resetErrListMessage() {
    errListMessage.innerHTML = `
        <ul id="err-list"class="mt-1.5 list-disc list-inside font-bold"></ul>
    `;
}

function generateMesssage(subMessage) {
    var ul = document.getElementById("err-list");
    var li = document.createElement("li");
    li.appendChild(document.createTextNode(subMessage));
    ul.appendChild(li);
}
