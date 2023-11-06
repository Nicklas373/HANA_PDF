import { Modal } from 'flowbite';

const $targetEl = document.getElementById('loadingModal');
const $newModal = document.getElementById('errModal');
const options = {
    placement: 'bottom-right',
    backdrop: 'dynamic',
    backdropClasses: 'bg-gray-900 bg-opacity-50 dark:bg-opacity-80 backdrop-filter backdrop-blur-sm fixed inset-0 z-40',
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
let form_submit = document.querySelector('form');

form_submit.onsubmit = function(event) {
    var errMessage = document.getElementById("errMessageModal");
    var errSubMessage = document.getElementById("errSubMessageModal");
    if (document.getElementById("filelist") !== null) {
        var input = document.getElementById('multiple_files').files;
        var extErr = false;
        var falseCount = 0;
        var trueCount = 0;
        for(var i=0;i<input.length;i++){
            var arrayFile = input[i];
            let multiFileSize = arrayFile.size;
            if (arrayFile.type == "application/pdf")
            {
                if (multiFileSize >= 25000000) {
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
                errSubMessage.innerHTML = "Supported file format: pdf";
                newModal.show();
            } else {
                event.preventDefault();
                errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                errSubMessage.style.visibility = null;
                newModal.show();
            }
        } else {
            errMessage.style.visibility = null;
            errSubMessage.style.visibility = null;
            newModal.hide();
            modal.show();
        }
    }
    if (document.getElementById("file_input") !== null) {
        if (document.getElementById('cnvFrPDF') !== null) {
            if (document.getElementById('submitBtn_1') !== null && !document.getElementById("file_input").value) {
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 25000000) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.style.visibility = null;
                        newModal.show();
                    } else {
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "Supported file format: pdf";
                    newModal.show();
                }
            }
        } else if (document.getElementById('cnvToPDF') !== null) {
            if (document.getElementById('submitBtn_1') !== null && !document.getElementById("file_input").value) {
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                || file.files[0].type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" ||
                file.files[0].type == "application/vnd.openxmlformats-officedocument.presentationml.presentation")
                {
                    if (fileSize >= 25000000) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.style.visibility = null;
                        newModal.show();
                    } else {
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "Supported file format: docx, xlsx, pptx";
                    newModal.show();
                }
            }
        } else if (document.getElementById('splitLayout1')) {
            if (document.getElementById('submitBtn_2') !== null && !document.getElementById("file_input").value) {
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                newModal.hide();
                modal.show();
            } else if (document.getElementById('submitBtn_3') !== null && !document.getElementById("file_input").value) {
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 25000000) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.style.visibility = null;
                        newModal.show();
                    } else {
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "Supported file format: pdf";
                    newModal.show();
                }
            }
        } else if (document.getElementById('wmLayout1')) {
            if (document.getElementById('submitBtn_1') !== null && !document.getElementById("file_input").value && !document.getElementById("wm_file_input")) {
                if (document.getElementById('watermarkText').value) {
                    errMessage.style.visibility = null;
                    errSubMessage.style.visibility = null;
                    newModal.hide();
                    modal.show();
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Please set text for watermark !";
                    errSubMessage.style.visibility = null;
                    newModal.show();
                }
            } else if (document.getElementById('submitBtn_1') !== null && !document.getElementById("file_input").value && document.getElementById("wm_file_input")) {
                if (document.getElementById("wm_file_input").value) {
                    var imgFile = document.getElementById("wm_file_input");
                    let fileSize = imgFile.files[0].size;
                    if (imgFile.files[0].type == "image/jpeg" || imgFile.files[0].type == "image/png") {
                        if (fileSize >= 25000000) {
                            event.preventDefault();
                            errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                            errSubMessage.style.visibility = null;
                            newModal.show();
                        } else {
                            errMessage.style.visibility = null;
                            errSubMessage.style.visibility = null;
                            newModal.hide();
                            modal.show();
                        }
                    } else {
                        event.preventDefault();
                        errMessage.innerHTML  = "Unsupported file format !";
                        errSubMessage.innerHTML = "Supported file format: jpg, png";
                        newModal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Please set image for watermark !";
                    errSubMessage.style.visibility = null;
                    newModal.show();
                }
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 25000000) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.style.visibility = null;
                        newModal.show();
                    } else {
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "Supported file format: pdf";
                    newModal.show();
                }
            }
        } else {
            if (document.getElementById('submitBtn_1') !== null && !document.getElementById("file_input").value) {
                errMessage.style.visibility = null;
                errSubMessage.style.visibility = null;
                newModal.hide();
                modal.show();
            } else {
                var file = document.getElementById("file_input");
                let fileSize = file.files[0].size;
                if (file.files[0].type == "application/pdf")
                {
                    if (fileSize >= 25000000) {
                        event.preventDefault();
                        errMessage.innerHTML  = "Uploaded file has exceeds the limit !";
                        errSubMessage.style.visibility = null;
                        newModal.show();
                    } else {
                        errMessage.style.visibility = null;
                        errSubMessage.style.visibility = null;
                        newModal.hide();
                        modal.show();
                    }
                } else {
                    event.preventDefault();
                    errMessage.innerHTML  = "Unsupported file format !";
                    errSubMessage.innerHTML = "Supported file format: pdf";
                    newModal.show();
                }
            }
        }
    }
    if (document.getElementById('urlToPDF') !== null) {
        if (document.getElementById('urlToPDF').value) {
            errMessage.style.visibility = null;
            errSubMessage.style.visibility = null;
            newModal.hide();
            modal.show();
        } else {
            event.preventDefault();
            errMessage.innerHTML  = "Please set URL address !";
            errSubMessage.style.visibility = null;
            newModal.show();
        }
    }
}
