<?php

require_once __DIR__ . '/vendor/autoload.php';

$httpClient = new \LINE\LINEBot\HTTPClient\CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
$bot = new \LINE\LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
$userlocalAccessID = getenv('USERLOCAL_ACCESS_ID');

$signature = $_SERVER["HTTP_" . \LINE\LINEBot\Constant\HTTPHeader::LINE_SIGNATURE];
try {
  $events = $bot->parseEventRequest(file_get_contents('php://input'), $signature);
} catch(\LINE\LINEBot\Exception\InvalidSignatureException $e) {
  error_log("parseEventRequest failed. InvalidSignatureException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownEventTypeException $e) {
  error_log("parseEventRequest failed. UnknownEventTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\UnknownMessageTypeException $e) {
  error_log("parseEventRequest failed. UnknownMessageTypeException => ".var_export($e, true));
} catch(\LINE\LINEBot\Exception\InvalidEventRequestException $e) {
  error_log("parseEventRequest failed. InvalidEventRequestException => ".var_export($e, true));
}


$emoji['office'] = hex2bin("F4808882F48085B7F48FBFBF");
$emoji['calendar'] = hex2bin("F4809082F48087A7F48FBFBF");
$emoji['time1'] = hex2bin("F4809482F480878AF48FBFBF");
$emoji['time2'] = hex2bin("F4809482F4808781F48FBFBF");
//$emoji['kao1'] = mb_convert_encoding(hex2bin("0001F623"), 'UTF-8', 'UTF-32');
//$emoji['uzu'] = mb_convert_encoding(hex2bin("0001F300"), 'UTF-8', 'UTF-32');

foreach ($events as $event) {
  if ($event instanceof \LINE\LINEBot\Event\MessageEvent\TextMessage) {
    $message = $event->getText();
    error_log('input:' . $message);
    file_put_contents("php://stderr", $message . "\n");
    //get userlocal.jp message;
    $response_message = getUserLocalMessage($message, $userlocalAccessID);
  } elseif ($event instanceof \LINE\LINEBot\Event\BeaconDetectionEvent) {
    $hdid = $event->getHwid();
    $response_message = "おかえり！！";// . $hdid;
    error_log('input:' . 'ビーコン検知');
  }
  $bot->replyText($event->getReplyToken(), $response_message);
}


function getUserLocalMessage($message, $userlocalAccessID){
    $base_url = 'https://chatbot-api.userlocal.jp/api/chat';
    $sendMessage = $base_url . '?message=' . $message .'&key=' . $userlocalAccessID;
    error_log('send:' . $sendMessage);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $sendMessage);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 証明書の検証を行わない
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);  // curl_execの結果を文字列で返す
    $response = curl_exec($curl);
    $result = json_decode($response, true);
    $response_message = $result['result'];

    error_log('response:' . $response_message);
    file_put_contents("php://stderr", $response_message . "\n");

    return $response_message;
}

?>