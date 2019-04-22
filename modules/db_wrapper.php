<?php

require_once ("secure.php");

class DB
{

    private $connection;

    public function __construct()
    {
        $servername = "localhost";
        $username = $GLOBALS["db_username"];
        $password = $GLOBALS["db_pass"];
        $database = "botipzff_bot";

        $this->connection = new mysqli($servername, $username, $password, $database);
        $this->connection->set_charset("utf8");
    }

    //тестить чи ми законектились до БД
    public function testConnection()
    {
        if ($this->connection->connect_error) {
            return $this->connection->connect_error;
        } else {
            return "OK";
        }
    }

    public function transitLessons($lessons){
        foreach ($lessons as $item){
            $time = $item['Час'];
            $audience = $item['Аудиторія'];
            $teacher = $item['Викладач'];
            $subject = $item['Предмет'];
            $group = $item['Група'];
            $oddness = $item['Парність'];
            $type = $item['Тип'];
            $weekday = $item['День'];
            $sql = "INSERT INTO `Lesson`(`time_`, `audience`, `teacher`, `subject`, `group_`, `oddness`, `type`, `weekday`) VALUES ('$time','$audience','$teacher','$subject','$group','$oddness','$type','$weekday')";
            $result = $this->connection->query($sql);
        }
    }

    public function getUserNavState($tgid)
    {
        $sql = "select * from User where tg_id = $tgid";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $row = $result->fetch_assoc();
            return $row['nav_state'];
        }
    }

    public function getUserCourse($tgid){
        $sql = "select * from User where tg_id = $tgid";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $row = $result->fetch_assoc();
            return $row['course'];
        }
    }

    public function getUserGroup($tgid){
        $sql = "select * from User where tg_id = $tgid";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $row = $result->fetch_assoc();
            return $row['group_'];
        }
    }

    public function getTeacherName($tgid){
        $sql = "select * from User where tg_id = $tgid";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $row = $result->fetch_assoc();
            return $row['teacher_name'];
        }
    }

    public function getTeacherSchedule($teacher, $weekday, $oddness){
        $sql = "select * from Lesson where weekday = '$weekday' and teacher = '$teacher' and oddness = '$oddness'";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $out = array();
            while($row = $result->fetch_assoc()){
                $out[] = $row;
            }
            return $out;
        }
    }

    public function addUser($tgid, $name, $surname, $nav_state = 0)
    {
        $sql = "insert into User (tg_id, name, surname, nav_state) values ($tgid, '$name', '$surname', $nav_state)";
        $result = $this->connection->query($sql);
    }

    public function removeUser($tgid){
        $sql = "delete from User where tg_id = $tgid";
        $result = $this->connection->query($sql);
    }

    public function updateUserNav($tgid, $nav)
    {
        $sql = "update User set nav_state = '$nav' where tg_id = '$tgid'";
        $result = $this->connection->query($sql);
    }

    public  function updateUserCourse($tgid, $course){
        $sql = "update User set course = '$course' where tg_id = '$tgid'";
        $result = $this->connection->query($sql);
    }

    public  function updateUserGroup($tgid, $group){
        $sql = "update User set group_ = '$group' where tg_id = '$tgid'";
        $result = $this->connection->query($sql);
    }

    public  function updateTeacherName($tgid, $teacher){
        $sql = "update User set teacher_name = '$teacher' where tg_id = '$tgid'";
        $result = $this->connection->query($sql);
    }
}

?>