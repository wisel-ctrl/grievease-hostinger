<?php
/**
 * Semaphore SMS API Tester with Number-Based Sender Option
 */

// Configuration
define('SEMAPHORE_API_KEY', '024cb8782cdb71b2925fb933f6f8635f');
define('DEFAULT_RECIPIENT', '09127065754');
define('DEFAULT_MESSAGE', 'Message from Semaphore API Tester');
define('DEFAULT_SENDER', 'SEMAPHORE');
define('NUMBER_SENDER', '09757935655'); // CHANGE THIS TO YOUR REGISTERED SEMAPHORE NUMBER

function runSMSTest() {
    echo "\nSemaphore SMS API Tester\n";
    echo "========================\n\n";

    // Get test parameters
    $recipient = readline("Enter recipient number (e.g. 639xxxxxxxxx) [".DEFAULT_RECIPIENT."]: ");
    $recipient = empty($recipient) ? DEFAULT_RECIPIENT : $recipient;
    
    $message = readline("Enter message [".DEFAULT_MESSAGE."]: ");
    $message = empty($message) ? DEFAULT_MESSAGE : $message;
    
    echo "\nChoose Sender Type:\n";
    echo "1. Alphanumeric Sender (e.g. SEMAPHORE)\n";
    echo "2. Number-Based Sender (using ".NUMBER_SENDER.")\n";
    $senderType = readline("Enter choice [1]: ");
    
    if ($senderType == '2') {
        $sender = NUMBER_SENDER;
        echo "Using Number-Based Sender: $sender\n";
    } else {
        $sender = readline("Enter alphanumeric sender name [".DEFAULT_SENDER."]: ");
        $sender = empty($sender) ? DEFAULT_SENDER : $sender;
    }

    $formattedNumber = formatPhoneNumber($recipient);
    if (!$formattedNumber) {
        die("Error: Invalid phone number format\n");
    }

    echo "\nSending to: $formattedNumber\n";
    echo "Message: $message\n";
    echo "Sender: $sender\n\n";

    $result = sendSMSviaSemaphore($formattedNumber, $message, $sender);

    echo "\nTest Results:\n";
    echo "------------\n";
    echo "Status: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "HTTP Code: " . $result['http_code'] . "\n";
    
    if (isset($result['api_response'][0]['message_id'])) {
        echo "Message ID: " . $result['api_response'][0]['message_id'] . "\n";
    }
    
    if (!empty($result['error'])) {
        echo "Error: " . $result['error'] . "\n";
    }
    
    echo "\nRaw API Response:\n";
    print_r($result['api_response']);
    
    // Additional check for number-based sender specifics
    if (is_numeric($sender)) {
        echo "\nNumber-Based Sender Notes:\n";
        echo "- Delivery rate is typically higher\n";
        echo "- Recipients can reply to this number\n";
        echo "- Shows as actual number instead of name\n";
    }
}

function sendSMSviaSemaphore($number, $message, $sender) {
    $ch = curl_init();
    
    $parameters = [
        'apikey' => SEMAPHORE_API_KEY,
        'number' => $number,
        'message' => $message,
        'sendername' => $sender
    ];
    
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $response = json_decode($output, true);
    
    $success = ($httpCode == 200 && isset($response[0]['status']) && 
               ($response[0]['status'] == 'Queued' || $response[0]['status'] == 'Pending'));
    
    return [
        'success' => $success,
        'http_code' => $httpCode,
        'api_response' => $response,
        'error' => $curlError ?: (!$success ? 'API returned failure status' : '')
    ];
}

function formatPhoneNumber($number) {
    $cleaned = preg_replace('/[^0-9]/', '', $number);
    
    if (strlen($cleaned) == 10 && $cleaned[0] == '9') {
        return '+63' . $cleaned;
    }
    
    if (strlen($cleaned) == 11 && $cleaned[0] == '0') {
        return '+63' . substr($cleaned, 1);
    }
    
    if (strlen($cleaned) > 10 && substr($cleaned, 0, 2) == '63') {
        return '+' . $cleaned;
    }
    
    if (substr($cleaned, 0, 1) == '+') {
        return $cleaned;
    }
    
    return false;
}

// Run the test
if (php_sapi_name() === 'cli') {
    runSMSTest();
} else {
    echo "<pre>";
    runSMSTest();
    echo "</pre>";
}