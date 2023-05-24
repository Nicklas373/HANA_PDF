function changeButtonColor_merge() {
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
                document.getElementById('submitBtn').style.color="#0f172a"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#0f172a"
                document.getElementById('submitBtn').style.color="#ffffff"
            }
        }
    });
}

function rotate_merge(val) {
    let rotated = document.getElementById('pdfImageCaption_'+val);
    rotation = (rotation + angle) % 360;
    rotated.style.transform = `rotate(${rotation}deg)`;
}

function remove_cnv() {
    var btnLayout = document.getElementById('submitBtn')
    var pdfImage = document.getElementById('pdfPreview');
    var pdfWMlayout = document.getElementById('grid-layout_2');
    btnLayout.style.display="block";
    pdfWMlayout.style.display="none";
    pdfImage.style.display="none";
}

function remove_merge(val) {
    var pdfImage = document.getElementById('pdfImage_'+val);
    pdfImage.style.display="none";
}

function init() {
    var fullPath = document.getElementById('caption').value;
    var btnLayout = document.getElementById('submitBtn')
    var mBtnLayout = document.getElementById('grid-layout')
    var mBtnLayout_2 = document.getElementById('grid-layout_2')
    var pdfLayout = document.getElementById('pdfCompLayout')
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#0f172a"
        btnLayout.style.display = "none"
        mBtnLayout.style.display = "block"
        mBtnLayout_2.style = null
        pdfLayout.style = null
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#0f172a"
        document.getElementById('submitBtn').style.color="#ffffff"
        btnLayout.style.display = "block"
        mBtnLayout.style.display = "none"
        mBtnLayout_2.style.display = "none"
        pdfLayout.style.display="none"
    }
}

init();