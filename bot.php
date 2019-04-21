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
       case 12:
           studentMainMenu($message, $bot);
           break;
       default:
           $bot->sendMessage($chatid, "What the fuck It even supposed to mean?");
    }
}

function welcome($message, $bot, $first_time = true){
    $db = $GLOBALS["db"];
    $phrases = $GLOBALS['phrases'];
    $json = new JSON($GLOBALS['schedule']);

    $chatid = $message->getChat()->getId();

    //$groups = array_chunk($json->getCourses(), 3, false);
    if ($first_time) {
        $db->addUser($message->getFrom()->getId(), $message->getFrom()->getFirstName(), $message->getFrom()->getLastName());
        $bot->sendMessage($chatid, $phrases['welcome']);
    }

    $roles = array(array("Для студента"), array("Для викладача"), array("Для аудиторії"));
    $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup($roles, true);

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

        $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("Сьогодні", "Завтра"), array("Тиждень", "Повернутись до вибору ролі")));
        $bot->sendMessage($chatid, $phrases['menu_welcome'], null, false, null, $keyboard);
    } else {
        $bot->sendMessage($chatid, 'Неправильна назва групи: '.$text.', спробуй ще раз', null, false, null, $keyboard);
    }
}

function studentMainMenu($message, $bot){
    $db = $GLOBALS['db'];
    $json = $GLOBALS['json'];
    $phrases = $GLOBALS['phrases'];

    $available = array("Сьогодні", "Завтра", "Тиждень", "Повернутись до вибору ролі","Понеділок", "Вівторок", "Середа", "Четвер", "П'ятниця", "Субота");
    $text = $message->getText();
    $chatid = $message->getChat()->getId();
    $userid = $message->getFrom()->getId();
    $main_keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("Сьогодні", "Завтра"), array("Тиждень", "Повернутись до вибору ролі")));

    $course = $db->getUserCourse($userid);
    $group = $db->getUserGroup($userid);

    $weekdays = array("Понеділок", "Вівторок", "Середа", "Четвер", "П'ятниця", "Субота");

    if (in_array($text, $available)){
        if ($text === "Тиждень"){
            $keyboard = new \TelegramBot\Api\Types\ReplyKeyboardMarkup(array(array("Понеділок", "Вівторок", "Середа"), array("Четвер", "П'ятниця", "Субота")));
            $bot->sendMessage($chatid, 'Вибери потрібний день тижня:', null, false, null, $keyboard);
        } else if ($text === "Сьогодні"){
            $date = getDateTime();
            $weekday = $date[1];
            $isodd = 0;
            $schedule = $json->getWeekdaySchedule($course, $group, $weekday, $isodd);
            $bot->sendMessage($chatid, sceduleBeautifier($schedule), null, false, null, $main_keyboard);

        } else if ($text === "Завтра"){
            $date = getDateTime();
            $weekday = $date[3];
            $isodd = 0;
            $schedule = $json->getWeekdaySchedule($course, $group, $weekday, $isodd);
            $bot->sendMessage($chatid, sceduleBeautifier($schedule), null, false, null, $main_keyboard);

        } else if (in_array($text, $weekdays)){
            $week = array("Понеділок" => 0, "Вівторок" => 1, "Середа" => 2, "Четвер" => 3, "П'ятниця" => 4, "Субота" => 5);
            $weekday = $week[$text];
            $isodd = 0;
            $schedule = $json->getWeekdaySchedule($course, $group, $weekday, $isodd);
            $bot->sendMessage($chatid, sceduleBeautifier($schedule), null, false, null, $main_keyboard);

        } else if ($text === "Повернутись до вибору ролі"){
            welcome($message, $bot, false);

        } else {
            $bot->sendMessage($chatid, $phrases['invalid_input'], null, false, null, $main_keyboard);
        }
    }
}

    function sceduleBeautifier($scedule){
    $phrases = $GLOBALS['phrases'];
    //=== OLD VERSION AND SHOULD BE REMASTERED!!!  ===//
        $out = "";
        if (!empty($scedule)){
            for ($i = 0; $i < sizeof($scedule); $i++){
                $out .= "[ ".($i + 1)." пара | ".$scedule[$i]["Час"]." ]\n";
                $out .= "\t📖\t ".$scedule[$i]["Предмет"]."\n";
                $out .= "\t👤\t ".$scedule[$i]["Викладач"]."\n";
                $out .= "\t🚪\t ".$scedule[$i]["Аудиторія"]."\n";
                $out .= "\t📌\t ".$scedule[$i]["Тип"]."\n";
                $out .= "\n";
            }
        } else $out = $phrases['no_lessons'];

        return $out;
    }

    function getDateTime(){
        date_default_timezone_set("Europe/Kiev");
        //date_default_timezone_set("Europe/Amsterdam");

        $result[0] = date("w", time()); //php weekday
        $result[1] = ($result[0] === 0) ? 5 : $result[0] - 1; //special weekday
        $result[2] = ($result[0] === 6) ? 0 : $result[0] + 1; //php tomorrow
        $result[3] = ($result[0] === 6 || $result[0] === 0) ? 0 : $result[0]; //special tomorrow
        $result[4] = date("d/m/Y - H:i");

        return $result;
    }



$bot->run();

?>