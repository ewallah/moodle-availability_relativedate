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
        global $DB, $USER;
        $course = $info->get_course();
        $calc = 0;
        switch ($this->relativestart) {
            case 1:
                $calc = $course->startdate + $this->calcdate();
                break;
            case 2:
                if ($course->enddate > 0) {
                    $calc = $course->enddate - $this->calcdate();
                } else {
                    return false;
                }
                break;
            case 3:
                $sql = 'SELECT GREATEST(ue.timestart, ue.timecreated) AS startdate FROM {user_enrolments} ue
                        JOIN {enrol} e on ue.enrolid = e.id WHERE e.courseid = ? AND ue.userid = ? ORDER by startdate DESC';
                $uid = ($userid != $USER->id) ? $userid : $USER->id;
                if ($lowest = $DB->get_records_sql($sql, [$course->id, $uid])) {
                    $lowest = reset($lowest);
                    $calc = $lowest->startdate + $this->calcdate();
                }
                break;
            default:
                return false;
        }
        $allow = time() >= $calc;
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
    public function get_description($full, $not, \core_availability\info $info) {
        global $DB, $USER;
        if ($not) {
            $str = $this->relativestart == 2 ? 'from' : 'until';
        } else {
            $str = $this->relativestart == 2 ? 'until' : 'from';
        }
        $str = ucfirst(get_string('direction_' . $str, 'availability_date')) . ' ';
        if ($this->relativedwm < 5) {
            $str .= $this->relativenumber . ' ';
            if ($this->relativenumber == 1) {
               $str .= self::option_dwm()[$this->relativedwm];
            } else {
               $str .= self::options_dwm()[$this->relativedwm];
            }
            $str .= ' ' . self::options_start($this->relativestart);
        }
        if ($full) {
            $conf = get_string('strftimedatetime', 'langconfig');
            $str .= ' (';
            $course = $info->get_course();
            switch ($this->relativestart) {
                case 1:
                    $str .= userdate($course->startdate + $this->calcdate(), $conf);
                    break;
                case 2:
                    if ($course->enddate == 0) {
                        return get_string('noenddate', 'availability_relativedate');
                    }
                    $str .= userdate($course->enddate - $this->calcdate(), $conf);
                    break;
                case 3:
                    $sql = 'SELECT GREATEST(ue.timestart, ue.timecreated) AS startdate FROM {user_enrolments} ue
                            JOIN {enrol} e on ue.enrolid = e.id WHERE e.courseid = ? AND ue.userid = ? ORDER by startdate DESC';
                    if ($lowest = $DB->get_records_sql($sql, [$course->id, $USER->id])) {
                        $lowest = reset($lowest);
                        $str .= userdate($lowest->startdate + $this->calcdate(), $conf);
                    }
                    break;
                default:
                    return '';
            }
            $str .= ')';
        }
        return $str;
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
        }
    }

    /**
     * Obtains a the options for hours days weeks months.
     *
     * @return array
     */
    public static function options_dwm() {
        return [
           1 => \core_text::strtolower(get_string('hours')),
           2 => \core_text::strtolower(get_string('days')),
           3 => \core_text::strtolower(get_string('weeks')),
           4 => \core_text::strtolower(get_string('months'))
        ];
    }


    /**
     * Obtains a the options for hour day week month.
     *
     * @return array
     */
    public static function option_dwm() {
        return [
           1 => \core_text::strtolower(get_string('hour')),
           2 => \core_text::strtolower(get_string('day')),
           3 => \core_text::strtolower(get_string('week')),
           4 => \core_text::strtolower(get_string('month'))
        ];
    }

    /**
     * Calculates days/weeks/months.
     *
     * @return int seconds.
     */
    private function calcdate() {
        switch ($this->relativedwm) {
            case 1:
                return 3600 * $this->relativenumber;
            case 2:
                return DAYSECS * $this->relativenumber;
            case 3:
                return WEEKSECS * $this->relativenumber;
            case 4:
                return WEEKSECS * 4 * $this->relativenumber;
        }
    }
}