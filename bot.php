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
$json = new JSON($schedule);


//listeners

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

$bot->command('wipe', function ($message) use ($bot) {
    wipe($message, $bot);
});

//functions

function wipe($message, $bot){
    $db = $GLOBALS['db'];
    $db->removeUser($message->getFrom()->getId());
    $bot->sendMessage($message->getChat()->getId(), "Користувача видалено");
}

function router($message, $bot){
    $db = $GLOBALS["db"];
    $phrases = $GLOBALS['phrases'];

    $chatid = $message->getChat()->getId();
    $userid = $message->getFrom()->getId();

    $nav = $db->getUserNavState($userid);

    //$bot->sendMessage($chatid, $phrases['service_works']);

    switch ($nav) {
       case -1:
           welcome($message, $bot);
           break;
       case 0:
           roleMenu($message, $bot);
           break;
       case 1:
           setStudentCourse($message, $bot);
           break;
       case 11:
           setStudentGroup($message, $bot);
           break;
       default:
           $bot->sendMessage($chatid, "What the fuck It even supposed to mean?");
    }
}

function welcome($message, $bot){
    $db = $GLOBALS["db"];
    $phrases = $GLOBALS['phrases'];
    $json = new JSON($GLOBALS['schedule']);

    //$groups = array_chunk($json->getCourses(), 3, false);

    $db->addUser($message->getFrom()->getId(), $message->getFrom()->getFirstName(), $message->getFrom()->getLastName());

    $roles = array(array("Для студента"), array("Для викладача"), array("Для аудиторії"));
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($roles, true);

    $chatid = $message->getChat()->getId();

    $bot->sendMessage($chatid, $phrases['welcome']);
    $bot->sendMessage($chatid, $phrases['select_role'], null, false, null, $keyboard);
}

function roleMenu($message, $bot){
    $chatid = $message->getChat()->getId();
    $phrases = $GLOBALS['phrases'];
    $db = $GLOBALS['db'];
    $json = $GLOBALS['json'];

    if ($message->getText() === "Для студента"){
        $db->updateUserNav($message->getFrom()->getId(), 1);
        $courses = $json->getCourses();
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array_chunk($courses, 3, false), true);
        $bot->sendMessage($chatid, $phrases['student_set_role']);
        $bot->sendMessage($chatid, "Вибери свій курс: ", null, false, null, $keyboard);

    } else if ($message->getText() === "Для викладача"){
        $bot->sendMessage($chatid, $phrases['teacher_set_role']);
        $db->updateUserNav($message->getFrom()->getId(), 2);
    } else if ($message->getText() === "Для аудиторії"){
        $bot->sendMessage($chatid, $phrases['audience_set_role']);
        $db->updateUserNav($message->getFrom()->getId(), 3);
    } else {
        $bot->sendMessage($chatid, $phrases['invalid_input']);
    }
}

function setStudentCourse($message, $bot){
    $chatid = $message->getChat()->getId();
    $text = $message->getText();

    $phrases = $GLOBALS['phrases'];
    $db = $GLOBALS['db'];
    $json = $GLOBALS['json'];

    $courses = $json->getCourses();
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array_chunk($courses, 3, false), true);

    if (in_array($text, $courses)){
        $bot->sendMessage($chatid, 'Курс встановлено: '.$text);
        $db->updateUserNav($message->getFrom()->getId(), 11);
        $db->updateUserCourse($message->getFrom()->getId(), $text);

        $groups = $json->getGroupsForCourse($text);
        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array_chunk($groups, 3, false), true);

        $bot->sendMessage($chatid, $phrases['select_group'], null, false, null, $keyboard);
    } else {
        $bot->sendMessage($chatid, 'Неправильна назва курсу: '.$text.', спробуй ще раз', null, false, null, $keyboard);
    }
}

function setStudentGroup($message, $bot){
    $chatid = $message->getChat()->getId();
    $text = $message->getText();

    $phrases = $GLOBALS['phrases'];
    $db = $GLOBALS['db'];
    $json = $GLOBALS['json'];

    $course = $db->getUserCourse($message->getFrom()->getId());
    $groups = $json->getGroupsForCourse($course);
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array_chunk($groups, 3, false), true);

    if (in_array($text, $groups)){
        $bot->sendMessage($chatid, 'Групу встановлено: '.$text);
        $db->updateUserNav($message->getFrom()->getId(), 12);
        $db->updateUserGroup($message->getFrom()->getId(), $text);
    } else {
        $bot->sendMessage($chatid, 'Неправильна назва групи: '.$text.', спробуй ще раз', null, false, null, $keyboard);
    }
}



$bot->run();

?>