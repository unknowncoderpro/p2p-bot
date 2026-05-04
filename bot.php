<?php

$BOT_TOKEN = getenv("BOT_TOKEN");
$CHAT_ID   = getenv("CHAT_ID");

function sendTelegram($msg, $BOT_TOKEN, $CHAT_ID) {

    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";

    $data = [
        "chat_id" => $CHAT_ID,
        "text" => $msg,
        "parse_mode" => "HTML"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $res = curl_exec($ch);
    curl_close($ch);

    echo $res . "\n";
}

$url = "https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search";

$payload = [
    "fiat" => "INR",
    "page" => 1,
    "rows" => 10,
    "tradeType" => "BUY",
    "asset" => "USDT",
    "payTypes" => ["UPIQRCode"],
    "filterType" => "tradable"
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['data'])) {
    die("No data\n");
}

$swapPrice = null;
$swapFound = false;

foreach ($data['data'] as $row) {

    $name = trim($row['advertiser']['nickName'] ?? "");
    $price = (float)$row['adv']['price'];

    if (strtolower($name) === "swapninja") {
        $swapPrice = $price;
        $swapFound = true;
    }
}

// ALERT: missing SwapNinja
if (!$swapFound) {
    sendTelegram("⚠️ SwapNinja missing from P2P list!", $BOT_TOKEN, $CHAT_ID);
    exit;
}

// ALERT: higher price exists
foreach ($data['data'] as $row) {

    $name = trim($row['advertiser']['nickName'] ?? "");
    $price = (float)$row['adv']['price'];

    if (strtolower($name) === "swapninja") continue;

    if ($price > $swapPrice) {

        sendTelegram(
            "🚨 <b>Price Above SwapNinja</b>\n\nSeller: $name\nPrice: ₹$price\nSwapNinja: ₹$swapPrice",
            $BOT_TOKEN,
            $CHAT_ID
        );

        break;
    }
}

echo "done\n";
?>
