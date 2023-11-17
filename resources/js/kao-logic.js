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
            procTitleMessageModal.innerHTML = "Processing PDF..."
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
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "";
                    errListTitleMessage.innerHTML = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: PDF");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                    errSubMessage.innerHTML = ""
                    errListTitleMessage.innerHTML = "Error message"
                    resetErrListMessage();
                    generateMesssage("Maximum file size 25 MB");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            } else {
                procTitleMessageModal.innerHTML = "Uploading PDF..."
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.hide();
                modal.show();
            }
        } else if (document.getElementById("multiple_files").value == '' && document.getElementById("fileAlt") == null && uploadBtn == true ||
                    document.getElementById("multiple_files").value == '' && document.getElementById("fileAlt") != null && uploadBtn == true) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Please choose PDF file !";
                        errSubMessage.innerHTML = ""
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.show();
        } else {
            event.preventDefault();
            errMessage.innerHTML  = "Index out of bound !";
            errSubMessage.innerHTML = "";
            errListTitleMessage.innerHTML = "Error message"
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
                    errMessage.innerHTML  = "Please choose PDF file !";
                    errSubMessage.innerHTML = ""
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.show();
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                if (document.getElementById('compPDF') !== null) {
                    if (!document.getElementById('comp-low').checked && !document.getElementById('comp-rec').checked && !document.getElementById('comp-high').checked) {
                        var compLow = document.getElementById('lowestChk')
                        var compMed = document.getElementById('recChk')
                        var compHigh = document.getElementById('highestChk')
                        event.preventDefault();
                        errMessage.innerHTML  = "Please fill out these fields !";
                        errSubMessage.innerHTML = "";
                        errListTitleMessage.innerHTML = "Required fields:"
                        errAltSubMessageModal.style = null;
                        resetErrListMessage();
                        generateMesssage("Compression Quality");
                        compLow.style.borderColor = '#dc2626'
                        compMed.style.borderColor = '#dc2626'
                        compHigh.style.borderColor = '#dc2626'
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerHTML = "Processing PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else  if (document.getElementById('cnvFrPDF') !== null) {
                    if (!document.getElementById('lowestChkA').checked && !document.getElementById('ulChkA').checked && !document.getElementById('recChkA').checked && !document.getElementById('highestChkA').checked) {
                        var cnvToImg = document.getElementById('lowestChk')
                        var cnvToPPTX = document.getElementById('ulChk')
                        var cnvToXLSX = document.getElementById('recChk')
                        var cnvToDOCX = document.getElementById('highestChk')
                        event.preventDefault();
                        errMessage.innerHTML  = "Please fill out these fields !";
                        errSubMessage.innerHTML = "";
                        errListTitleMessage.innerHTML = "Required fields:"
                        errAltSubMessageModal.style = null;
                        resetErrListMessage();
                        generateMesssage("Document Format");
                        cnvToImg.style.borderColor = '#dc2626'
                        cnvToPPTX.style.borderColor = '#dc2626'
                        cnvToXLSX.style.borderColor = '#dc2626'
                        cnvToDOCX.style.borderColor = '#dc2626'
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerHTML = "Processing PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    procTitleMessageModal.innerHTML = "Processing PDF..."
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
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.innerHTML = ""
                        errListTitleMessage.innerHTML = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerHTML = "Uploading PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "";
                    errListTitleMessage.innerHTML = "Error message"
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
                    errMessage.innerHTML  = "Please choose document file !";
                    errSubMessage.innerHTML = ""
                    errSubMessage.style.visibility = null;
                    errAltSubMessageModal.style.display = "none";
                    newModal.show();
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                procTitleMessageModal.innerHTML = "Processing Document..."
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                || file.files[0].type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
                file.files[0].type == "application/vnd.openxmlformats-officedocument.presentationml.presentation")
                {
                    if (fileSize >= 26214400) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.innerHTML = ""
                        errListTitleMessage.innerHTML = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerHTML = "Upload Document"
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "";
                    errListTitleMessage.innerHTML = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: DOCX, XLSX, PPTX");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            }
        } else if (document.getElementById('splitLayout1')) {
            if (!document.getElementById("file_input").value && uploadBtn == false) {
                if (document.getElementById("SplitOpta").checked) {
                    let cusPage = false;
                    let fromPage = false;
                    let toPage = false;
                    var customPage = document.getElementById('customPage')
                    var firstPage = document.getElementById('fromPage')
                    var lastPage = document.getElementById('toPage')
                    if (document.getElementById("SplitOpta").value == "split") {
                        if (document.getElementById("splitRadio")) {
                           if (document.getElementById("SplitOpt2a").checked) {
                                if (document.getElementById("SplitOpt2a").value == "selPages") {
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
                                        if (parseInt(document.getElementById("fromPage").value) >= parseInt(document.getElementById("toPage").value)) {
                                            event.preventDefault();
                                            errMessage.innerHTML  = "Invalid page number range !";
                                            errListTitleMessage.innerHTML = "Error message"
                                            errAltSubMessageModal.style = null;
                                            resetErrListMessage();
                                            generateMesssage("First page can not more than last page");
                                            firstPage.style.borderColor = '#dc2626'
                                            newModal.show();
                                        } else {
                                            procTitleMessageModal.innerHTML = "Processing PDF..."
                                            errMessage.style.visibility = null;
                                            errSubMessage.style.visibility = null;
                                            errAltSubMessageModal.style.display = "none";
                                            newModal.hide();
                                            modal.show();
                                        }
                                    } else if (!fromPage && !toPage) {
                                        event.preventDefault();
                                        errMessage.innerHTML  = "Please fill out these fields !";
                                        errSubMessage.innerHTML = "";
                                        errListTitleMessage.innerHTML = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("First Pages");
                                        generateMesssage("Last Pages");
                                        firstPage.style.borderColor = '#dc2626'
                                        lastPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else if (!fromPage && toPage) {
                                        event.preventDefault();
                                        errMessage.innerHTML  = "Please fill out these fields !";
                                        errSubMessage.innerHTML = "";
                                        errListTitleMessage.innerHTML = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("First Pages");
                                        firstPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else if (fromPage && !toPage) {
                                        event.preventDefault();
                                        errMessage.innerHTML  = "Please fill out these fields !";
                                        errSubMessage.innerHTML = "";
                                        errListTitleMessage.innerHTML = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("Last Pages");
                                        lastPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    } else {
                                        procTitleMessageModal.innerHTML = "Processing PDF..."
                                        errMessage.style = null;
                                        errSubMessage.style = null;
                                        errAltSubMessageModal.style.display = "none";
                                        newModal.hide();
                                        modal.show();
                                    }
                                } else {
                                    event.preventDefault();
                                    errMessage.innerHTML  = "Index out of bound !";
                                    errSubMessage.innerHTML = "";
                                    errAltSubMessageModal.style = null;
                                    errListTitleMessage.innerHTML = "Error message"
                                    resetErrListMessage();
                                    generateMesssage("Split selected page logic error");
                                    errAltSubMessageModal.style = null;
                                    newModal.show();
                                }
                            } else if (document.getElementById("SplitOpt2b").checked) {
                                if (document.getElementById("SplitOpt2b").value == "cusPages") {
                                    if (document.getElementById("customPage").value) {
                                         cusPage = true;
                                    } else {
                                         cusPage = false;
                                    }
                                    if (cusPage) {
                                        procTitleMessageModal.innerHTML = "Processing PDF..."
                                         errMessage.style.visibility = null;
                                         errSubMessage.style.visibility = null;
                                         errAltSubMessageModal.style.display = "none";
                                         newModal.hide();
                                         modal.show();
                                    } else {
                                        event.preventDefault();
                                        errMessage.innerHTML  = "Please fill out these fields !";
                                        errSubMessage.innerHTML = "";
                                        errListTitleMessage.innerHTML = "Required fields:"
                                        errAltSubMessageModal.style = null;
                                        resetErrListMessage();
                                        generateMesssage("Custom Pages");
                                        customPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    }
                                } else {
                                    event.preventDefault();
                                    errMessage.innerHTML  = "Index out of bound !";
                                    errSubMessage.innerHTML = "";
                                    errListTitleMessage.innerHTML = "Error message"
                                    resetErrListMessage();
                                    generateMesssage("Split custom page logic error");
                                    errAltSubMessageModal.style = null;
                                    newModal.show();
                                }
                             } else {
                                event.preventDefault();
                                errMessage.innerHTML  = "Index out of bound !";
                                errSubMessage.innerHTML = "";
                                errListTitleMessage.innerHTML = "Error message"
                                resetErrListMessage();
                                generateMesssage("Cannot define selected or custom page");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerHTML  = "Kaori";
                            errSubMessage.style.visibility = null;
                            errAltSubMessageModal.style.display = "none";
                            newModal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerHTML  = "Index out of bound !";
                        errSubMessage.innerHTML = "";
                        errListTitleMessage.innerHTML = "Error message"
                        resetErrListMessage();
                        generateMesssage("Split options decision logic error");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    }
                } else if (document.getElementById("SplitOptb").checked) {
                    let cusPage = false;
                    var customPage = document.getElementById('customPage')
                    if (document.getElementById("SplitOptb").value == "extract") {
                            if (document.getElementById("customPage").value) {
                                 cusPage = true;
                            } else {
                                 cusPage = false;
                            }
                            if (cusPage) {
                                procTitleMessageModal.innerHTML = "Processing PDF..."
                                errMessage.style.visibility = null;
                                errSubMessage.style.visibility = null;
                                errAltSubMessageModal.style.display = "none";
                                newModal.hide();
                                modal.show();
                            } else {
                                event.preventDefault();
                                errMessage.innerHTML  = "Please fill out these fields !";
                                errSubMessage.innerHTML = "";
                                errListTitleMessage.innerHTML = "Required fields:"
                                errAltSubMessageModal.style = null;
                                resetErrListMessage();
                                generateMesssage("Custom Pages");
                                errSubMessage.style.visibility = null;
                                customPage.style.borderColor = '#dc2626'
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerHTML  = "Index out of bound !";
                            errSubMessage.innerHTML = "";
                            errListTitleMessage.innerHTML = "Error message"
                            resetErrListMessage();
                            generateMesssage("Extract options decision logic error");
                            errAltSubMessageModal.style = null;
                            newModal.show();
                        }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Index out of bound !";
                    errSubMessage.innerHTML = "";
                    errListTitleMessage.innerHTML = "Error message"
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
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.innerHTML = ""
                        errListTitleMessage.innerHTML = "Error message"
                        resetErrListMessage();
                        generateMesssage("Maximum file size 25 MB");
                        errAltSubMessageModal.style = null;
                        newModal.show();
                    } else {
                        procTitleMessageModal.innerHTML = "Uploading PDF..."
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "";
                    errListTitleMessage.innerHTML = "Error message"
                    resetErrListMessage();
                    generateMesssage("Supported file format: PDF");
                    errAltSubMessageModal.style = null;
                    newModal.show();
                }
            } else {
                event.preventDefault();
                errMessage.innerHTML  = "Please choose PDF file !";
                errSubMessage.innerHTML = ""
                errSubMessage.style.visibility = null;
                errAltSubMessageModal.style.display = "none";
                newModal.show();
            }
        } else if (document.getElementById('wmLayout1')) {
            if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == false) {
                if (document.getElementById('wmType') != null) {
                    if (document.getElementById('wmType').value == 'text') {
                        var wmText = document.getElementById("watermarkText");
                        if (!document.getElementById('watermarkText').value && !document.getElementById('watermarkPage').value) {
                            var wmPage = document.getElementById("watermarkPage");
                            event.preventDefault();
                            errMessage.innerHTML  = "Please fill out these fields !";
                            errSubMessage.innerHTML = "";
                            errListTitleMessage.innerHTML = "Required fields:"
                            resetErrListMessage();
                            generateMesssage("Pages");
                            generateMesssage("Text");
                            errAltSubMessageModal.style = null;
                            wmText.style.borderColor = '#dc2626'
                            wmPage.style.borderColor = '#dc2626'
                            newModal.show();
                        } else if (document.getElementById('watermarkText').value) {
                            if (document.getElementById('watermarkPage').value) {
                                errMessage.style.visibility = null;
                                procTitleMessageModal.innerHTML = "Processing PDF..."
                                errSubMessage.style.visibility = null;
                                errAltSubMessageModal.style.display = "none";
                                newModal.hide();
                                modal.show();
                            } else {
                                var wmPage = document.getElementById("watermarkPage");
                                event.preventDefault();
                                errMessage.innerHTML  = "Please fill out these fields !";
                                errSubMessage.innerHTML = "";
                                errListTitleMessage.innerHTML = "Required fields:"
                                resetErrListMessage();
                                generateMesssage("Pages");
                                errAltSubMessageModal.style = null;
                                wmPage.style.borderColor = '#dc2626'
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerHTML  = "Please fill out these fields !";
                            errSubMessage.innerHTML = "";
                            errListTitleMessage.innerHTML = "Required fields:"
                            resetErrListMessage();
                            generateMesssage("Text");
                            errAltSubMessageModal.style = null;
                            wmText.style.borderColor = '#dc2626'
                            newModal.show();
                        }
                    } else if (document.getElementById('wmType').value == 'image') {
                        var wmImage = document.getElementById("wm_file_input");
                        if (document.getElementById("wm_file_input").value) {
                            var imgFile = document.getElementById("wm_file_input");
                            let fileSize = imgFile.files[0].size;
                            if (imgFile.files[0].type == "image/jpeg" || imgFile.files[0].type == "image/png") {
                                if (fileSize >= 5242880) {
                                    event.preventDefault();
                                    errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                                    errSubMessage.innerHTML = ""
                                    errListTitleMessage.innerHTML = "Error message"
                                    resetErrListMessage();
                                    generateMesssage("Maximum file size 5 MB");
                                    errAltSubMessageModal.style = null;
                                    newModal.show();
                                } else {
                                    if (document.getElementById('watermarkPage').value) {
                                        procTitleMessageModal.innerHTML = "Processing PDF..."
                                        errMessage.style.visibility = null;
                                        errSubMessage.style.visibility = null;
                                        errAltSubMessageModal.style.display = "none";
                                        newModal.hide();
                                        modal.show();
                                    } else {
                                        var wmPage = document.getElementById("watermarkPage");
                                        event.preventDefault();
                                        errMessage.innerHTML  = "Please fill out these fields !";
                                        errSubMessage.innerHTML = "";
                                        errListTitleMessage.innerHTML = "Required fields:"
                                        resetErrListMessage();
                                        generateMesssage("Pages");
                                        errAltSubMessageModal.style = null;
                                        wmPage.style.borderColor = '#dc2626'
                                        newModal.show();
                                    }
                                }
                            } else {
                                event.preventDefault();
                                errMessage.innerHTML  = "Unsupported file format !";
                                errSubMessage.innerHTML = "";
                                errListTitleMessage.innerHTML = "Error message"
                                resetErrListMessage();
                                generateMesssage("Supported file format: JPG, PNG");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerHTML  = "Please fill out these fields !";
                            errSubMessage.innerHTML = "";
                            errListTitleMessage.innerHTML = "Required fields:"
                            resetErrListMessage();
                            generateMesssage("Image");
                            errAltSubMessageModal.style = null;
                            wmImage.style.borderColor = '#dc2626'
                            newModal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerHTML  = "Please choose watermark options !";
                        errSubMessage.innerHTML = ""
                        errSubMessage.style.visibility = null;
                        errAltSubMessageModal.style.display = "none";
                        newModal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Please choose watermark options !";
                    errSubMessage.innerHTML = ""
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
                                errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                                errSubMessage.innerHTML = ""
                                errListTitleMessage.innerHTML = "Error message"
                                resetErrListMessage();
                                generateMesssage("Maximum file size 25 MB");
                                errAltSubMessageModal.style = null;
                                newModal.show();
                            } else {
                                procTitleMessageModal.innerHTML = "Uploading PDF..."
                                errMessage.style.visibility = null;
                                errSubMessage.style.visibility = null;
                                errAltSubMessageModal.style.display = "none";
                                newModal.hide();
                                modal.show();
                            }
                        } else {
                            event.preventDefault();
                            errMessage.innerHTML  = "Unsupported file format !";
                            errSubMessage.innerHTML = "";
                            errListTitleMessage.innerHTML = "Error message"
                            resetErrListMessage();
                            generateMesssage("Supported file format: PDF");
                            errAltSubMessageModal.style = null;
                            newModal.show();
                        }
            } else if (!document.getElementById("file_input").value && document.getElementById("fileAlt") != null && uploadBtn == true ||
                        !document.getElementById("file_input").value && document.getElementById("fileAlt") == null && uploadBtn == true) {
                            event.preventDefault();
                            errMessage.innerHTML  = "Please choose PDF file !";
                            errSubMessage.innerHTML = ""
                            errSubMessage.style.visibility = null;
                            errAltSubMessageModal.style.display = "none";
                            newModal.show();
            } else {
                event.preventDefault();
                errMessage.innerHTML  = "Index out of bound !";
                errSubMessage.innerHTML = "";
                errListTitleMessage.innerHTML = "Error message"
                resetErrListMessage();
                generateMesssage("Watermark decision logic error");
                errAltSubMessageModal.style = null;
                newModal.show();
            }
        } else {
            event.preventDefault();
            errMessage.innerHTML  = "Index out of bound !";
            errSubMessage.innerHTML = "";
            errListTitleMessage.innerHTML = "Error message"
            resetErrListMessage();
            generateMesssage("PDF decision logic error");
            errAltSubMessageModal.style = null;
            newModal.show();
        }
    }
    if (document.getElementById('urlToPDF') !== null) {
        var urlAddr = document.getElementById('urlToPDF')
        if (document.getElementById('urlToPDF').value) {
            procTitleMessageModal.innerHTML = "Processing URL..."
            errMessage.style.visibility = null;
            errSubMessage.style.visibility = null;
            errAltSubMessageModal.style.display = "none";
            newModal.hide();
            modal.show();
        } else {
            event.preventDefault();
            errMessage.innerHTML  = "Please fill out these fields !";
            errSubMessage.innerHTML = "";
            errListTitleMessage.innerHTML = "Required fields:"
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
