<?php
    $c = curl_init();
    $apiKey = env('PDFTABLES_API_KEY');
    curl_setopt($c, CURLOPT_URL, "https://pdftables.com/api/remaining?key=$apikey");
    $result = curl_exec($c);

    if (curl_errno($c) > 0) {
        echo "Error !";
    } else {
        echo trim($result, "1");
    }

    curl_close($c);
?>