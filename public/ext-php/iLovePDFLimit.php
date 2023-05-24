<?php
    use Ilovepdf\Ilovepdf;

    $ilovepdf = new Ilovepdf(env('ILOVEPDF_PUBLIC_KEY'),env('ILOVEPDF_SECRET_KEY'));

    //get remaining files
    $remainingFiles = $ilovepdf->getRemainingFiles();

    //count total usage
    $totalUsage = 250 - $remainingFiles;

    //print your remaining files
    echo strval($totalUsage);