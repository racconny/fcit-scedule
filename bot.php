<?php

require_once ("vendor/autoload.php");
require_once ("secure.php");
require_once ("modules/db_wrapper.php");
require_once ("modules/json_wrapper.php");
require_once ("phrases.php");

// init
$bot = new \TelegramBot\Api\Client($token);

// message processing mechanism will be here..
$bot->on(function($Update) use ($bot){
    $phrases = $GLOBALS['phrases'];
    $message = $Update->getMessage();
    $text = $message->getText();
    $chatid = $message->getChat()->getId();

    $bot->sendMessage($chatid, $phrases['service_works']);

}, function($message) use ($bot){
    return true;
});

$bot->run();

?>