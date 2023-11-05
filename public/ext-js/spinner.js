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
                document.getElementById('submitBtn').style.color="#38bdf8"
                document.getElementById('file_input').style.backgroundColor="#e2e8f0"
            } else {
                document.getElementById('submitBtn').style.backgroundColor="#38bdf8"
                document.getElementById('submitBtn').style.color="#ffffff"
                document.getElementById('file_input').style.backgroundColor="#f8fafc"
            }
        }
    });
}

function LowChkClick() {
    document.getElementById("lowestChk").style.borderColor = '#38bdf8'
    document.getElementById("lowest-txt").style.color = '#38bdf8'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
    document.getElementById("highestChk").style.borderColor = '#e2e8f0'
    document.getElementById("highest-txt").style.color = '#1e293b'
    document.getElementById("ulChk").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt").style.color = '#1e293b'
    document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
    document.getElementById("submitBtn_1").style.color = "#e2e8f0"
}

function RecChkClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#38bdf8'
    document.getElementById("rec-txt").style.color = '#38bdf8'
    document.getElementById("highestChk").style.borderColor = '#e2e8f0'
    document.getElementById("highest-txt").style.color = '#1e293b'
    document.getElementById("ulChk").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt").style.color = '#1e293b'
    document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
    document.getElementById("submitBtn_1").style.color = "#e2e8f0"
}

function HighChkClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
    document.getElementById("highestChk").style.borderColor = '#38bdf8'
    document.getElementById("highest-txt").style.color = '#38bdf8'
    document.getElementById("ulChk").style.borderColor = '#e2e8f0'
    document.getElementById("ul-txt").style.color = '#1e293b'
    document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
    document.getElementById("submitBtn_1").style.color = "#e2e8f0"
}

function UlChkClick() {
    document.getElementById("lowestChk").style.borderColor = '#e2e8f0'
    document.getElementById("lowest-txt").style.color = '#1e293b'
    document.getElementById("recChk").style.borderColor = '#e2e8f0'
    document.getElementById("rec-txt").style.color = '#1e293b'
    document.getElementById("highestChk").style.borderColor = '#e2e8f0'
    document.getElementById("highest-txt").style.color = '#1e293b'
    document.getElementById("ulChk").style.borderColor = '#38bdf8'
    document.getElementById("ul-txt").style.color = '#38bdf8'
    document.getElementById("submitBtn_1").style.backgroundColor="#38bdf8"
    document.getElementById("submitBtn_1").style.color = "#e2e8f0"
}
