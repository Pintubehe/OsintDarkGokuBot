<?php
// --------------------------------------------------------------------------------
// CONFIGURATION - Vercel Compatible
// --------------------------------------------------------------------------------
define('BOT_TOKEN', '8597238500:AAEFOIVTity_z34TjLL4a39bKSGQrAk0P_k');
define('CHANNEL_LINK', 'https://t.me/Gokuuuu00');
define('API_KEY_SPLEXXO', 'SPLEXXO');
define('BOT_USERNAME', '@Osinttt_goku7u_bot'); // Without @

// Error Reporting for Vercel
error_reporting(E_ALL);
ini_set('display_errors', 0); // Vercel pe errors show nahi kare
ini_set('log_errors', 1);

// Set headers for Vercel
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --------------------------------------------------------------------------------
// HELPER FUNCTIONS - Vercel Optimized
// --------------------------------------------------------------------------------

function botRequest($method, $parameters = []) {
    $url = 'https://api.telegram.org/bot' . BOT_TOKEN . '/' . $method;

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($handle, CURLOPT_TIMEOUT, 30); // Reduced for Vercel
    curl_setopt($handle, CURLOPT_POST, true);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "User-Agent: Vercel-Telegram-Bot"
    ]);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);
    
    $response = curl_exec($handle);
    $http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    
    // Log for debugging
    if ($http_code !== 200) {
        error_log("Telegram API Error: HTTP $http_code - Method: $method");
    }
    
    return json_decode($response, true);
}

function fetchUrl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Reduced for Vercel
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Vercel-Bot)');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
    
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("cURL Error: $error - URL: $url");
    }
    
    return ['data' => $data, 'code' => $http_code];
}

function get_buttons() {
    $perms = "change_info+delete_messages+restrict_members+invite_users+pin_messages+manage_video_chats+promote_members";
    $add_group_url = "https://t.me/" . BOT_USERNAME . "?startgroup=true&admin=$perms";
    
    return [
        'inline_keyboard' => [
            [
                ['text' => '📢 Join Channel', 'url' => CHANNEL_LINK],
                ['text' => '➕ Add Me To Group', 'url' => $add_group_url]
            ]
        ]
    ];
}

function check_group_and_args($chat_id, $chat_type, $args, $example_cmd) {
    if ($chat_type === 'private') {
        botRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "❌ <b>ACCESS DENIED</b>\nYeh bot sirf <b>Groups</b> mein kaam karta hai.\nNeeche diye gaye button se ise apne group mein add karein.",
            'parse_mode' => 'HTML',
            'reply_markup' => get_buttons()
        ]);
        return false;
    }

    if (empty($args)) {
        botRequest('sendMessage', [
            'chat_id' => $chat_id,
            'text' => "⚠️ <b>Missing Input!</b>\nExample: <code>$example_cmd</code>",
            'parse_mode' => 'HTML'
        ]);
        return false;
    }
    return true;
}

function fetch_and_reply($chat_id, $message_id, $url, $target, $title, $remove_texts = []) {
    $sent = botRequest('sendMessage', [
        'chat_id' => $chat_id,
        'text' => "🔄 <b>Fetching Data...</b>\nTarget: <code>$target</code>",
        'parse_mode' => 'HTML',
        'reply_to_message_id' => $message_id
    ]);

    $status_msg_id = $sent['result']['message_id'] ?? null;

    try {
        $res = fetchUrl($url);
        
        if ($res['code'] == 200) {
            $json_data = json_decode($res['data'], true);
            $data = ($json_data === null) ? ["response" => $res['data'], "note" => "Raw Text Response"] : $json_data;
        } else {
            $data = ["error" => "API Error", "status" => $res['code']];
        }

        $formatted_json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (!empty($remove_texts)) {
            foreach ($remove_texts as $word) {
                $formatted_json = str_replace($word, "", $formatted_json);
            }
        }

        // Limit response size for Vercel
        if (strlen($formatted_json) > 3500) {
            $formatted_json = substr($formatted_json, 0, 3500) . "\n... (truncated)";
        }

        $result_text = "<b>$title ☠️</b>\nTarget: <code>$target</code>\n\n<b>JSON RESPONSE:</b>\n<pre language='json'>$formatted_json</pre>\n\n<b>Bot Developer:</b> @gokuuuu_1";

        if ($status_msg_id) {
            botRequest('editMessageText', [
                'chat_id' => $chat_id,
                'message_id' => $status_msg_id,
                'text' => $result_text,
                'parse_mode' => 'HTML',
                'reply_markup' => get_buttons()
            ]);
        }
    } catch (Exception $e) {
        error_log("Error in fetch_and_reply: " . $e->getMessage());
    }
}

// --------------------------------------------------------------------------------
// WEBHOOK SETUP FOR VERCEL
// --------------------------------------------------------------------------------

// GET request handling (for setup and health checks)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['setup'])) {
        // Auto-detect Vercel URL
        $protocol = 'https://';
        $host = $_SERVER['HTTP_HOST'];
        $webhook_url = $protocol . $host . '/api';
        
        $res = botRequest('setWebhook', ['url' => $webhook_url]);
        
        $response = [
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['ok'] ? 'Webhook set successfully' : 'Failed to set webhook',
            'webhook_url' => $webhook_url,
            'telegram_response' => $res,
            'bot_info' => botRequest('getMe'),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    if (isset($_GET['remove'])) {
        $res = botRequest('setWebhook', ['url' => '']);
        
        $response = [
            'status' => $res['ok'] ? 'success' : 'error',
            'message' => $res['ok'] ? 'Webhook removed successfully' : 'Failed to remove webhook',
            'telegram_response' => $res
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    if (isset($_GET['status'])) {
        $webhookInfo = botRequest('getWebhookInfo');
        $botInfo = botRequest('getMe');
        
        $response = [
            'status' => 'online',
            'bot' => $botInfo['result']['username'] ?? 'Unknown',
            'webhook' => [
                'url' => $webhookInfo['result']['url'] ?? 'Not set',
                'has_custom_certificate' => $webhookInfo['result']['has_custom_certificate'] ?? false,
                'pending_update_count' => $webhookInfo['result']['pending_update_count'] ?? 0
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Vercel',
                'request_time' => date('Y-m-d H:i:s'),
                'uptime' => time() - $_SERVER['REQUEST_TIME']
            ]
        ];
        
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    // Default GET response
    $response = [
        'message' => 'Telegram Bot API',
        'description' => 'This is a Telegram bot running on Vercel',
        'endpoints' => [
            'POST /' => 'Telegram webhook endpoint',
            'GET /?setup=1' => 'Set webhook automatically',
            'GET /?remove=1' => 'Remove webhook',
            'GET /?status=1' => 'Check bot status'
        ],
        'bot' => '@' . BOT_USERNAME,
        'host' => 'Vercel',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// --------------------------------------------------------------------------------
// MAIN LOGIC - Telegram Webhook
// --------------------------------------------------------------------------------

// Read Telegram update
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// Log the update for debugging
error_log("Telegram Update Received: " . json_encode($update));

if (!$update) {
    // No update received, send OK response
    echo json_encode(['status' => 'ok', 'message' => 'No update data']);
    exit;
}

if (isset($update['message']['text'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $chat_type = $message['chat']['type'];
    $text = $message['text'];
    $msg_id = $message['message_id'];

    // Command logic
    $parts = explode(' ', trim($text));
    $command = strtolower($parts[0]);
    if (strpos($command, '@') !== false) {
        $command = substr($command, 0, strpos($command, '@'));
    }
    
    $args = array_slice($parts, 1);

    switch ($command) {
        case '/start':
            if ($chat_type === 'private') {
                botRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "❌ <b>ACCESS DENIED</b>\nYeh bot sirf <b>Groups</b> mein kaam karta hai.\nNeeche diye gaye button se ise apne group mein add karein.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => get_buttons()
                ]);
            } else {
                $text = "<b>Available Commands:</b>\n📱 /num\n🚗 /vehicle\n💸 /upiinfo\n🏦 /fam\n📸 /insta\n🌐 /ip\n📧 /email\n📨 /tg\n🏦 /ifsc\n🆔 /adhar\n📱 /imei\n📲 /bomber\n🇵🇰 /pak\n👨‍👩‍👦 /family\n🧾 /gst\n💀 /user2num\n🎮 /freefire\n💻 /git\n🔢 /numinfo\n\n<b>Bot Developer:</b> @gokuuuu_1";
                botRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => get_buttons()
                ]);
            }
            break;
        
        case '/list':
        case '/lookup':
            if ($chat_type === 'private') {
                botRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => "❌ Sirf Group me.",
                    'parse_mode' => 'HTML',
                    'reply_markup' => get_buttons()
                ]);
            } else {
                $text = "<b>Available Commands:</b>\n📱 /num\n🚗 /vehicle\n💸 /upiinfo\n🏦 /fam\n📸 /insta\n🌐 /ip\n📧 /email\n📨 /tg\n🏦 /ifsc\n🆔 /adhar\n📱 /imei\n📲 /bomber\n🇵🇰 /pak\n👨‍👩‍👦 /family\n🧾 /gst\n💀 /user2num\n🎮 /freefire\n💻 /git\n🔢 /numinfo\n\n<b>Bot Developer:</b> @gokuuuu_1";
                botRequest('sendMessage', [
                    'chat_id' => $chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => get_buttons()
                ]);
            }
            break;

        case '/num':
            if (check_group_and_args($chat_id, $chat_type, $args, "/num 99xxxxxx99")) {
                $url = "https://splexxo-bhia-to.vercel.app/api/seller?mobile=" . urlencode($args[0]) . "&key=" . API_KEY_SPLEXXO;
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "INDIAN LOOKUP");
            }
            break;

        case '/numinfo':
            if (check_group_and_args($chat_id, $chat_type, $args, "/numinfo 7724019785")) {
                $url = "https://num-to-email-all-info-api.vercel.app/?mobile=" . urlencode($args[0]) . "&key=GOKU";
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "NUMBER INFO");
            }
            break;

        case '/vehicle':
        case '/veh':
            if (check_group_and_args($chat_id, $chat_type, $args, "/vehicle KA04EQ4521")) {
                $url = "https://splexxo-vncl-info-acik.vercel.app/api/seller?vehicle=" . urlencode($args[0]) . "&key=" . API_KEY_SPLEXXO;
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "VEHICLE INFO");
            }
            break;

        case '/tg':
            if (check_group_and_args($chat_id, $chat_type, $args, "/tg username")) {
                $username = str_replace("@", "", $args[0]);
                $url = "https://rajanlink.gt.tc/telegram.php?username=" . urlencode($username);
                fetch_and_reply($chat_id, $msg_id, $url, $username, "TELEGRAM INFO", ["@rajanhakerd", "Rajan"]);
            }
            break;

        case '/ip':
            if (check_group_and_args($chat_id, $chat_type, $args, "/ip 1.1.1.1")) {
                $url = "https://ip-info.hosters.club/?ip=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "IP LOOKUP", ["https://t.me/DrSudo"]);
            }
            break;

        case '/ifsc':
            if (check_group_and_args($chat_id, $chat_type, $args, "/ifsc SBIN000123")) {
                $url = "https://ifsc-code-info.gauravcyber0.workers.dev/?ifsc=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "IFSC LOOKUP");
            }
            break;

        case '/pin':
            if (check_group_and_args($chat_id, $chat_type, $args, "/pin 201301")) {
                $url = "https://pin-code-info.gauravcyber0.workers.dev/?pincode=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "PINCODE INFO");
            }
            break;
        
        case '/pk':
            if (check_group_and_args($chat_id, $chat_type, $args, "/pk 92300...")) {
                $url = "https://pakistan-num-info.gauravcyber0.workers.dev/?pakistan=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "PAKISTAN DB");
            }
            break;

        case '/ig':
        case '/insta':
            if (check_group_and_args($chat_id, $chat_type, $args, "/insta username")) {
                $url = "https://instagram-info.gauravcyber0.workers.dev/?username=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "INSTAGRAM INFO");
            }
            break;

        case '/familyinfo':
        case '/fam':
        case '/adhar':
        case '/family':
            if (check_group_and_args($chat_id, $chat_type, $args, "/adhar 123456789012")) {
                $url = "http://splexxo-ad-api.vercel.app/api/aadhaar?aadhaar=" . urlencode($args[0]) . "&key=" . API_KEY_SPLEXXO;
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "AADHAAR INFO");
            }
            break;

        case '/cnic':
            if (check_group_and_args($chat_id, $chat_type, $args, "/cnic 12345")) {
                $url = "https://cnic-info.gauravcyber0.workers.dev/?cnic=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "CNIC INFO");
            }
            break;

        case '/freefire':
        case '/ff':
            if (check_group_and_args($chat_id, $chat_type, $args, "/freefire 123456789")) {
                $url = "https://splexxo-ff-emot.vercel.app/api/seller?message=" . urlencode($args[0]) . "&key=" . API_KEY_SPLEXXO;
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "FREE FIRE INFO");
            }
            break;

        case '/git':
            if (check_group_and_args($chat_id, $chat_type, $args, "/git username")) {
                $url = "https://pytoday-git-repo-search.vercel.app/?query=" . urlencode($args[0]);
                fetch_and_reply($chat_id, $msg_id, $url, $args[0], "GITHUB REPO INFO");
            }
            break;
            
        default:
            // Unknown command - do nothing
            break;
    }
}

// Always send OK response to Telegram
echo json_encode(['ok' => true]);
?>
