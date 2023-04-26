<?php
    use Ilovepdf\Ilovepdf;

    $ilovepdf = new Ilovepdf('project_public_325d386bc0c634a66ce67d65413fe30c_GE-Cv2861de258f64776f2928e69cb4868675','secret_key_a704c544b92db47bc422a824c6b3004e_QZVE20e592b1888ab4c21fca2f1b170b20f8b');

    //get remaining files
    $remainingFiles = $ilovepdf->getRemainingFiles();

    //count total usage
    $totalUsage = 250 - $remainingFiles;

    //print your remaining files
    echo strval($totalUsage);