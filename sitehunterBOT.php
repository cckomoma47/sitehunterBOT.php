<?php

// ===== 1. NO SECRET TOKEN CHECK (since we didn't set one) =====
// ============================================================

// ===== 2. SHOW STATUS WHEN VISITED IN BROWSER =====
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['webhook'])) {
    echo "✅ Bot is running!";
    echo "<br>Webhook URL: https://sitehunterbot-php.onrender.com/sitehunterBOT.php";
    echo "<br>Bot Token: " . substr('8641593682:AAHiMVXQbin-rQKJ_OOYn8F_PAlWVIsKPjg', 0, 10) . "...";
    exit;
}

// ===== 3. YOUR BOT CODE =====
define('BOT_TOKEN', '8641593682:AAHiMVXQbin-rQKJ_OOYn8F_PAlWVIsKPjg');
define('API_URL', 'https://api.telegram.org/bot' . BOT_TOKEN . '/');

// Payment gateway terms
$gatewayTerms = [
    "adyen", "paypal", "braintree", "stripe", "square", "authorize.net", 
    "payu", "payubiz", "razorpay", "cash on delivery", "mobikwik", "google pay", 
    "amazon pay", "apple pay", "visa", "mastercard", "payment gateway"
];

// Function to analyze a website
function analyzeWebsite($url, $gatewayTerms)
{
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headers = curl_getinfo($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            return "❌ Unable to fetch the website. HTTP Code: $httpCode";
        }

        $detectedGateways = [];
        foreach ($gatewayTerms as $term) {
            if (stripos($response, $term) !== false) {
                $detectedGateways[] = $term;
            }
        }

        $captchaPresent = stripos($response, 'g-recaptcha') !== false || stripos($response, 'captcha') !== false;
        $cloudflarePresent = isset($headers['cf-ray']) || (isset($headers['Set-Cookie']) && strpos($headers['Set-Cookie'], '__cfduid') !== false);
        $graphqlPresent = stripos($response, '/graphql') !== false;

        $platform = "Unknown";
        if (stripos($response, "wp-content") !== false) {
            $platform = "WordPress";
        } elseif (stripos($response, "shopify") !== false) {
            $platform = "Shopify";
        } elseif (stripos($response, "magento") !== false) {
            $platform = "Magento";
        }

        $result = "🔍 Gateways Fetched Successfully ✅\n";
        $result .= "━━━━━━━━━━━━━\n";
        $result .= "🚀 URL: $url 🔗\n";
        $result .= "🚀 Payment Gateways: " . (empty($detectedGateways) ? "None" : implode(", ", $detectedGateways)) . "\n";
        $result .= "🚀 Captcha: " . ($captchaPresent ? "True 🤡" : "False 🔥") . "\n";
        $result .= "🚀 Cloudflare: " . ($cloudflarePresent ? "True 🙂" : "False 😋") . "\n";
        $result .= "🚀 GraphQL: " . ($graphqlPresent ? "True ✅" : "False ❌") . "\n";
        $result .= "🚀 Platform: $platform ❤️‍🔥\n";
        $result .= "━━━━━━━━━━━━━\n";
        $result .= "🤖 Bot by: @Awmtee";

        return $result;
    } catch (Exception $e) {
        return "❌ Error: " . $e->getMessage();
    }
}

// Function to send messages via Telegram
function sendMessage($chatId, $message)
{
    $url = API_URL . "sendMessage";
    $postData = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $result = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Log errors for debugging
    if ($error) {
        error_log("SendMessage Error: " . $error);
    }
    return $result;
}

// ===== 4. HANDLE INCOMING UPDATES =====
$update = json_decode(file_get_contents("php://input"), true);

// Log the update for debugging
error_log("Received update: " . json_encode($update));

if ($update && isset($update['message'])) {
    $message = $update['message'];
    $chatId = $message['chat']['id'];
    $text = isset($message['text']) ? $message['text'] : '';

    if (strpos($text, '/start') === 0) {
        $welcomeMessage = "👋 Welcome to the Gateway and Query Bot!\n\n";
        $welcomeMessage .= "🔹 Use /gate <URL> to analyze a website.\n";
        sendMessage($chatId, $welcomeMessage);
    } elseif (strpos($text, '/gate') === 0) {
        $parts = explode(' ', $text, 2);
        if (count($parts) < 2) {
            sendMessage($chatId, "❌ Please provide a URL. Example: /gate https://example.com");
        } else {
            $url = $parts[1];
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                sendMessage($chatId, "❌ Invalid URL. Make sure it starts with http:// or https://.");
            } else {
                sendMessage($chatId, "🔍 Fetching details... Please wait.");
                $result = analyzeWebsite($url, $gatewayTerms);
                sendMessage($chatId, $result);
            }
        }
    } 
}

// Always return 200 OK to Telegram
http_response_code(200);
?>
