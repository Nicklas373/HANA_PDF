var currentUsage = document.getElementById("progressValue").textContent;
var limitUsage = 250;
var totalUsage = (limitUsage - currentUsage);
var totalUsagePercentage = (totalUsage*100)/limitUsage;
document.getElementById("progressValue").innerText = totalUsage + "/250";
document.getElementById("progressBar").style.width = totalUsagePercentage.toFixed(2) + "%";