<?php


class JSON
{

    private $json;

    /**
     * Constructor for JSON
     *
     * @param string $path path to the json file
     */
    public function __construct($path)
    {
        $string = file_get_contents($path);
        $this->json = json_decode($string, true);
    }

    /**
     * Use this method to get array of courses (tabs in xls document)
     *
     * @return array of courses
     */
    public function getCourses()
    {
        $result = array();
        for ($i = 0; $i < sizeof($this->json); $i++) {
            $result[] = mb_strtoupper($this->json[$i][0]);
        }
        return $result;
    }

    /**
     * Use this method get array of groups within a course
     *
     * @return array of groups
     */
    public function getGroupsForCourse($course)
    {
        $result = array();
        for ($i = 0; $i < sizeof($this->json); $i++) {
            if (mb_strtoupper($this->json[$i][0]) == mb_strtoupper($course)) {
                for ($j = 0; $j < sizeof($this->json[$i][1]); $j++) {
                    $str = $this->json[$i][1][$j][0];
                    $result[] = mb_convert_encoding($str, mb_detect_encoding($str), "UTF-8");
                }
            }
        }
        return $result;
    }

    /**
     * Use this method to get a list of all groups of oll courses in the document
     *
     * @return array of groups
     */
    public function getAllGroups()
    {
        $courses = $this->getCourses();
        $result = [];
        for ($i = 0; $i < sizeof($courses); $i++) {
            $tmp = $this->getGroupsForCourse($courses[$i]);
            $result = array_merge($result, $tmp);
        }

        return $result;
    }

    /**
     * Use this method to get daily scedule
     *
     * @param string $course course which you get from getCourses()
     * @param string $group which you get from getGroupsForCourse()
     * @param int $weekday 0 - 3 where 0 is Monday, 3 is Thursday
     * @param int $isodd 0|1 0 is odd, 1 is not
     *
     * @return array of associative arrays with keys of Час, Аудиторія, Викладач, Предмет, Тип
     */
    public function getWeekdaySchedule($course, $group, $weekday, $isodd)
    {
        $result = array();
        for ($i = 0; $i < sizeof($this->json); $i++) {
            if (mb_strtoupper($this->json[$i][0]) == mb_strtoupper($course)) {
                for ($j = 0; $j < sizeof($this->json[$i][1]); $j++) {
                    if ($this->json[$i][1][$j][0] == $group) {
                        for ($k = 0; $k < sizeof($this->json[$i][1][$j][1][$weekday][$isodd]); $k++) {
                            if (isset($this->json[$i][1][$j][1][$weekday][$isodd][$k]["Предмет"])) {
                                $item = $this->json[$i][1][$j][1][$weekday][$isodd][$k];
                                $result[] = $item;
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }
}


?>