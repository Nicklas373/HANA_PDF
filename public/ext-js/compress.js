function init() {
    var fullPath = document.getElementById('caption').value;
    var pdfLayout = document.getElementById('pdfCompLayout');
    var btnLayout = document.getElementById('submitBtn_1');
    document.getElementById("fileAlt").style.display = "none";
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#38bdf8"
        document.getElementById('file_input').style.backgroundColor="#f8fafc"
        pdfLayout.style = null
        btnLayout.style = null
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
        document.getElementById('submitBtn').style.color="#e2e8f0"
        document.getElementById('file_input').style.backgroundColor="#e2e8f0"
        pdfLayout.style.display = "none"
        btnLayout.style.display = "none"
    }
}

init();
