<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Date condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

defined('MOODLE_INTERNAL') || die();

/**
 * relativedate from course start condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int relativenumber (how many relative) for condition. */
    private $relativenumber;

    /** @var int relativedwm (what does the date relates to) for condition.
     *
     * 1 => hours
     * 2 => days
     * 3 => weeks
     * 4 => months
     */
    private $relativedwm;

    /** @var int relativestart (what date relates to) for condition.
     *
     * 1 => After Course start date
     * 2 => Before Course end date
     * 3 => After User enrolment date
     * 4 => After Enrolment method end date
     */
    private $relativestart;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        $this->relativenumber = property_exists($structure, 'n') ? (int)$structure->n : 1;
        $this->relativedwm = property_exists($structure, 'd') ? (int)$structure->d : 2;
        $this->relativestart = property_exists($structure, 's') ? (int)$structure->s : 1;
    }

    /**
     * Saves the data.
     *
     * @return object data structure.
     */
    public function save() {
        return (object)[
            'type' => 'relativedate',
            'n' => intval($this->relativenumber),
            'd' => intval($this->relativedwm),
            's' => intval($this->relativestart)];
    }

    /**
     * Determines whether this particular item is currently available.
     *
     * @param bool $not
     * @param \core_availability\info $info
     * @param bool $grabthelot
     * @param int $userid If set, specifies a different user ID to check availability for
     * @return bool True if this item is available to the user, false otherwise
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $calc = $this->calc($info->get_course(), $userid);
        $allow = ($calc == 0) ? false : time() >= $calc;
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not it actually applies).
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on this item
     */
    public function get_description($full, $not, \core_availability\info $info): string {
        global $USER;
        $course = $info->get_course();
        $context = \context_course::instance($info->get_course()->id);
        $capability = has_capability('moodle/course:manageactivities', $context);
        if ($this->relativestart === 2) {
            if ($course->enddate == 0 and $capability) {
                return get_string('noenddate', 'availability_relativedate');
            }
            $frut = $not ? 'from' : 'until';
        } else {
            $frut = $not ? 'until' : 'from';
        }
        $calc = $this->calc($course, $USER->id);
        if ($calc == 0) {
            return '';
        }
        $a = new \stdClass();
        $a->rnumber = userdate($calc, get_string('strftimedatetime', 'langconfig'));
        $a->rtime = $capability ? '('. trim($this->get_debug_string()) . ')' : '';
        $a->rela = '';
        return trim(get_string($frut, 'availability_relativedate', $a));
    }

    /**
     * Obtains a representation of the options of this condition as a string for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return ' ' . $this->relativenumber . ' ' . self::options_dwm()[$this->relativedwm] . ' ' .
               self::options_start($this->relativestart);
    }

    /**
     * Obtains a the options for days week months.
     *
     * @param int $i index
     * @return string
     */
    public static function options_start(int $i) {
        switch ($i) {
            case 1:
                return get_string('datestart', 'availability_relativedate');
            case 2:
                return get_string('dateend', 'availability_relativedate');
            case 3:
                return get_string('dateenrol', 'availability_relativedate');
            case 4:
                return get_string('dateendenrol', 'availability_relativedate');
        }
    }

    /**
     * Obtains a the options for hours days weeks months.
     *
     * @return array
     */
    public static function options_dwm() {
        return [
            1 => get_string('hours', 'availability_relativedate'),
            2 => get_string('days', 'availability_relativedate'),
            3 => get_string('weeks', 'availability_relativedate'),
            4 => get_string('months', 'availability_relativedate')
        ];
    }

    /**
     * Obtains a the options for hour day week month.
     *
     * @param int $i
     * @return string
     */
    public static function option_dwm(int $i):string {
        switch ($i) {
            case 1:
                return 'hour';
            case 2:
                return 'day';
            case 3:
                return 'week';
            case 4:
                return 'month';
        }
        return '';
    }

    /**
     * Perform the calculation.
     *
     * @param stdClass $course
     * @param int $userid
     * @return int relative date.
     */
    private function calc($course, $userid):int {
        global $DB, $USER;
        $x = $this->option_dwm($this->relativedwm);
        if ($this->relativestart == 1) {
            $calc = strtotime("+$this->relativenumber $x", $course->startdate);
            return $this->fixcalc($calc, $course->startdate);
        } else if ($this->relativestart == 2) {
            if ($course->enddate != 0) {
                $calc = strtotime("-$this->relativenumber $x", $course->enddate);
                return $this->fixcalc($calc, $course->enddate);
            }
        } else if ($this->relativestart == 3) {
            $uid = ($userid != $USER->id) ? $userid : $USER->id;
            $sql = 'SELECT MAX(GREATEST(ue.timestart, ue.timecreated)) AS uedate
                    FROM {user_enrolments} ue
                    JOIN {enrol} e on ue.enrolid = e.id
                    WHERE e.courseid = ? AND ue.userid = ?
                    ORDER by uedate DESC
                    LIMIT 1';
            if ($lowest = $DB->get_record_sql($sql, [$uid, $course->id, $uid])) {
                $lowest = reset($lowest);
                $lowest = ($lowest == 0) ? time() : $lowest;
                $calc = strtotime("+$this->relativenumber $x", $lowest);
                return $this->fixcalc($calc, $lowest);
            }
        } else if ($this->relativestart == 4) {
            $uid = ($userid != $USER->id) ? $userid : $USER->id;
            $sql = 'SELECT e.enrolenddate
                    FROM {user_enrolments} ue
                    JOIN {enrol} e on ue.enrolid = e.id
                    WHERE e.courseid = ? AND ue.userid = ?
                    ORDER by e.enrolenddate DESC
                    LIMIT 1';
            if ($lowest = $DB->get_record_sql($sql, [$course->id, $uid])) {
                $lowest = reset($lowest);
                $lowest = ($lowest == 0) ? time() : $lowest;
                $calc = strtotime("+$this->relativenumber $x", $lowest);
                return $this->fixcalc($calc, $lowest);
            }
        }
        return 0;
    }

    /**
     * Keep the original hour.
     *
     * @param int $olddate
     * @param int $newdate
     * @return int relative date.
     */
    private function fixcalc ($olddate, $newdate) {
        if ($this->relativedwm > 1) {
            $arr1 = getdate($olddate);
            $arr2 = getdate($newdate);
            return mktime($arr2['hours'], $arr2['minutes'], $arr2['seconds'], $arr1['mon'], $arr1['mday'], $arr1['year']);
        }
        return $olddate;
    }
}