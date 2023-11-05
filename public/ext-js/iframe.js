function reloadIFrame() {
    var iframe = document.getElementById("iFrame");
    if (iframe !== null) {
        if (iframe.contentDocument !== null) {
            console.log(iframe.contentDocument.URL); //work control
            if(iframe.contentDocument.URL == "about:blank"){
                iframe.src =  iframe.src;
            }
            document.getElementById("iFrame").style.display = "none"
            document.getElementById("iFrameBorder").style.display = null
        } else {
            clearInterval(timerId);
            document.getElementById("iFrameBorder").style.display = "none"
            document.getElementById("iFrame").style.display = null
        }
    }

}

var timerId = setInterval("reloadIFrame();", 2000);
