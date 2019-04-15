<?php

require_once ("secure.php");

class DB {

    private $connection;

    public function __construct(){
        $servername = "localhost";
        $username = $GLOBALS["db_username"];
        $password = $GLOBALS["db_pass"];
        $database = "botipzff_bot";

        $this->connection = new mysqli($servername, $username, $password, $database);
        $this->connection->set_charset("utf8");
    }

    //тестить чи ми законектились до БД
    public function testConnection(){
        if ($this->connection->connect_error) {
            return $this->connection->connect_error;
        } else {
            return "OK";
        }
    }

    public function getUserNavState($tgid){
        $sql = "select * from User where tg_id = $tgid";
        $result = $this->connection->query($sql);

        if ($result->num_rows == 0) {
            return -1;
        } else {
            $row = $result->fetch_assoc();
            return $row['nav_state'];
        }
    }

    public function addUser($tgid, $name, $surname, $nav_state = 0){
        $sql = "insert into User (tg_id, name, surname, nav_state) values ($tgid, '$name', '$surname', $nav_state)";
        $result = $this->connection->query($sql);
    }

}

?>