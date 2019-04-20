<?php

require_once ("vendor/autoload.php");
require_once ("secure.php");
require_once ("modules/db_wrapper.php");
require_once ("modules/json_wrapper.php");
require_once ("phrases.php");

// init
$bot = new \TelegramBot\Api\Client($token);
$db = new DB();
$schedule = 'resources/schedule.json';




$bot->on(function($Update) use ($bot){
    if ($Update->getMessage()->getText()[0] !== "/"){
        router($Update->getMessage(), $bot);
    }
}, function($message) use ($bot){
    return true;
});

$bot->command('start', function ($message) use ($bot) {
    router($message, $bot);
});



function router($message, $bot){
    $db = $GLOBALS["db"];
    $phrases = $GLOBALS['phrases'];

    $chatid = $message->getChat()->getId();
    $userid = $message->getFrom()->getId();

    $nav = $db->getUserNavState($userid);

    //$bot->sendMessage($chatid, $phrases['service_works']);

    if ($nav === -1){
        $db->addUser($userid, $message->getFrom()->getFirstName(), $message->getFrom()->getLastName());
        welcome($message, $bot);
    }
}

function welcome($message, $bot){
    $db = $GLOBALS["db"];
    $phrases = $GLOBALS['phrases'];
    $json = new JSON($GLOBALS['schedule']);

    $groups = array_chunk($json->getCourses(), 3, false);
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($groups, true);

    $chatid = $message->getChat()->getId();

    $bot->sendMessage($chatid, $phrases['welcome']);
    $bot->sendMessage($chatid, $phrases['select_course'], null, false, null, $keyboard);
}



$bot->run();

?>