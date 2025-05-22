<?php
$app_id = '1097671002177530';
$app_secret = 'af9d6ad39fb6db04940dced5c376ebc8'; // Replace with your app secret

// Generate app access token
$app_access_token = file_get_contents("https://graph.facebook.com/oauth/access_token?client_id={$app_id}&client_secret={$app_secret}&grant_type=client_credentials");

// Your domain list
$domains = array(
    'grievease.com',
    'www.grievease.com'
);

// Convert array to JSON
$domains_json = json_encode($domains);

// Make the API call to update domains
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v18.0/{$app_id}");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
    'app_domains' => $domains_json,
    'access_token' => $app_access_token
)));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check response
$result = json_decode($response, true);
if ($http_code == 200 && isset($result['success']) && $result['success']) {
    echo "App domains updated successfully!";
} else {
    echo "Error updating app domains: " . $response;
}
?> 