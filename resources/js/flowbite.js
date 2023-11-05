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
    var file = document.getElementById("file_input");
    var multiFile = document.getElementById("filelist");
    var errMessage = document.getElementById("errMessageModal");
    var errSubMessage = document.getElementById("errSubMessageModal");
    if (multiFile !== null) {
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
    } else {
        let fileSize = file.files[0].size;
        if (document.getElementById('cnvFrPDF') !== null) {
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
        } else if (document.getElementById('cnvToPDF') !== null) {
            if (file.files[0].type == "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            || file.files[0].type == "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" || file.files[0].type == "application/vnd.openxmlformats-officedocument.presentationml.presentation")
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
        } else {
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
