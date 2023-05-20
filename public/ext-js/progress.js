function init() {
    var currentUsage = document.getElementById("progressValue").textContent;
    var limitUsage = 250;
    var totalUsagePercentage = (currentUsage*100)/limitUsage;
    document.getElementById("progressValue").innerText = currentUsage + "/250";
    document.getElementById("progressBar").style.width = totalUsagePercentage.toFixed(2) + "%";
};

init();