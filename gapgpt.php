<?php

$apiKey = "sk-h6QdnmKcfIU3wTQFhZk9f6Wz7KetYgjN6HAR7eXagPX0tAbS";
$apiUrl = "https://api.gapgpt.app/v1/chat/completions"; // آدرس دقیق را از مستندات GapGPT بردارید

$data = [
    "model" => "gapgpt-qwen-3.5",
    "messages" => [
        ["role" => "user", "content" => "In the Social Workers list, when we click on any row, it should smoothly expand in an animated accordion style and display the supervisors that are under the coverage of that social worker. The supervisors should be shown as rows with the following columns:

Auto-increment Row Number
Supervisor National ID
Supervisor Full Name
Supervisor Mobile Number
Number of Beneficiaries Under Supervision"],
    ]
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $apiKey"
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);

// بررسی خطای cURL
if(curl_errno($ch)){
    echo 'خطای cURL: ' . curl_error($ch) . "\n";
} else {
    // چاپ پاسخ خام API برای بررسی مشکل
    echo "پاسخ خام سرور:\n" . $response . "\n\n";

    $result = json_decode($response, true);

    if (isset($result['choices'][0]['message']['content'])) {
        echo "جواب هوش مصنوعی:\n" . $result['choices'][0]['message']['content'] . "\n";
    } else {
        echo "خطا: ساختار پاسخ با چیزی که انتظار داشتیم متفاوت است.\n";
    }
}

curl_close($ch);
