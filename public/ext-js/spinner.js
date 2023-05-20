const angle = 90;
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

function onClick() {
    document.getElementById("grid-layout").style.opacity = "0.25";
}

function rotate() {
    const rotated = document.getElementById('pdfImageCaption');
    rotation = (rotation + angle) % 360;
    rotated.style.transform = `rotate(${rotation}deg)`;
}

function remove() {
    var pdfCompBtn = document.getElementById('submitBtn_1');
    var pdfComp = document.getElementById('pdfCompLayout');
    var pdfImage = document.getElementById('pdfPreview');
    var pdfSplit1 = document.getElementById("splitLayout1");
    var pdfSplit2 = document.getElementById("splitLayout2");
    pdfCompBtn.style.display="none";
    pdfComp.style.display="none";
    pdfImage.style.display="none";
    pdfSplit1.style.display="none";
    pdfSplit2.style.display="none";
}