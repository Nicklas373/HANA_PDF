const angle = 90;
let rotation = 0;

function onClick() {
    document.getElementById("grid-layout").style.opacity = "0.25";
    document.getElementById("spinner").style.visibility = "visible";
}

function rotate() {
    const rotated = document.getElementById('pdfImageCaption');
    rotation = (rotation + angle) % 360;
    rotated.style.transform = `rotate(${rotation}deg)`;
}

function rotate_merge(val) {
    let rotated = document.getElementById('pdfImageCaption_'+val);
    rotation = (rotation + angle) % 360;
    rotated.style.transform = `rotate(${rotation}deg)`;
}

function remove() {
    var pdfImage = document.getElementById('pdfImage')
    pdfImage.style.visibility="hidden"
}

function remove_merge(val) {
    var pdfImage = document.getElementById('pdfImage_'+val);
    pdfImage.style.visibility="hidden";
}