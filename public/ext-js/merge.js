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
                document.getElementById('submitBtn').style.color="#38bdf8"
                document.getElementById('multiple_files').style.backgroundColor="#e2e8f0"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="#ffffff"
                document.getElementById('multiple_files').style.backgroundColor="#f8fafc"
            }
        }
    });
}

function init() {
    var fullPath = document.getElementById('caption').value;
    var btnLayout = document.getElementById('submitBtn')
    var mergeLayout = document.getElementById('submitBtn_1')
    document.getElementById("fileAlt").style.display = "none";
    if (fullPath !== '') {
        document.getElementById('submitBtn').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn').style.color="#38bdf8"
        document.getElementById('multiple_files').style.backgroundColor="#f8fafc"
        btnLayout.style = null
        mergeLayout.style = "none"
    } else {
        document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
        document.getElementById('submitBtn').style.color="#e2e8f0"
        document.getElementById('multiple_files').style.backgroundColor="#e2e8f0"
        btnLayout.style.display = "none"
        mergeLayout.style = null
    }
}

init();
