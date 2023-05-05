const angle = 90;
const rotated = document.getElementById('pdfImageCaption');
let rotation = 0;

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

function rotate() {
    rotation = (rotation + angle) % 360;
    rotated.style.transform = `rotate(${rotation}deg)`;
}

function init() {
    var fullPath = document.getElementById('caption').value;
    var pdfLayout = document.getElementById('pdfCompLayout')
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#0f172a"
        pdfLayout.style.visibility="visible"
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#0f172a"
        document.getElementById('submitBtn').style.color="#ffffff"
        pdfLayout.style.visibility="hidden"
    }
}

function remove() {
    var pdfImage = document.getElementById('pdfImage')
    pdfImage.style.visibility="hidden"
}

init();