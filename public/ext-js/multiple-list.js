document.getElementById('multiple_files').addEventListener('change', function(e) {
    var list = document.getElementById('filelist');
    list.innerHTML = "";
    for (var i = 0; i < this.files.length; i++) {
      list.innerHTML += (i + 1) + '. ' + this.files[i].name + ' (' + formatBytes(this.files[i].size) + ')' +'\n';
    }
    if (list.innerHTML == '') list.style.display = 'none';
    else list.style.display = 'block';
});

function formatBytes(bytes, decimals = 2) {
    if (!+bytes) return '0 Bytes'

    const k = 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}