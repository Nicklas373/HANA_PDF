function changeButtonColor() {
    document.getElementById('multiple_files').addEventListener('change', function(e) {
        var fullPath = document.getElementById('multiple_files').value;
        if (fullPath) {
            var startIndex = (fullPath.indexOf('\\') >= 0 ? fullPath.lastIndexOf('\\') : fullPath.lastIndexOf('/'));
            var filename = fullPath.substring(startIndex);
            if (filename.indexOf('\\') === 0 || filename.indexOf('/') === 0) {
                filename = filename.substring(1);
            }
            if (filename == "") {
                document.getElementById('submitBtn_1').style.backgroundColor="#e2e8f0"
                document.getElementById('submitBtn_1').style.color="#0f172a"
            } else {
                document.getElementById('submitBtn_1').style.backgroundColor="#0f172a"
                document.getElementById('submitBtn_1').style.color="#ffffff"
            }
        }
    });
}


function init() {
    var fullPath = document.getElementById('caption').value;
    var btnLayout = document.getElementById('submitBtn_2')
    if (fullPath !== '') {
        document.getElementById('submitBtn_1').style.backgroundColor="#e2e8f0"
        document.getElementById('submitBtn_1').style.color="#0f172a"
        btnLayout.style.visibility="visible"
    } else {
        document.getElementById('submitBtn_1').style.backgroundColor="#0f172a"
        document.getElementById('submitBtn_1').style.color="#ffffff"
        btnLayout.style.visibility="hidden"
    }
}

init();