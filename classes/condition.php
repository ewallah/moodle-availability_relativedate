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
 * @copyright 2014 Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

defined('MOODLE_INTERNAL') || die();

/**
 * relativedate from course start condition.
 *
 * @package availability_relativedate
 * @copyright 2014 Valery Fremaux
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int relativenumber (how many relative) for condition. */
    private $relativenumber;

    /** @var int relativedwm (what does the date relates to) for condition.
     *
     * 1 => days
     * 2 => weeks
     * 3 => months
     */
    private $relativedwm;

    /** @var int relativestart (what date relates to) for condition.
     *
     * 1 => After Course start date
     * 2 => Before Course end date
     * 3 => After user enrolment date
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
            'n' => (int)$this->relativenumber,
            'd' => (int)$this->relativedwm,
            's' => (int)$this->relativestart];
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
        $calc = $this->calcstart($info, $userid);
        if ($calc == 0) {
            // Return false if for some reason the calculation returns 0.
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
        $calc = $this->calcstart($info, 0);
        $nstr = $not ? 'Not ' : '';
        if ($full) {
            if ($calc == 0) {
                $dstr = ' (No course enddate)';
            } else {
                $dstr = ' (' . userdate($calc, get_string('strftimedatetime', 'langconfig')) . ')';
            }
            $str = $nstr . $this->relativenumber . ' ';
            $str .= strtolower(get_string(self::options_dwm()[$this->relativedwm])) . ' ';
            $str .= strtolower(self::options_start($this->relativestart)) . $dstr;
            return $str;
        } else {
            $bstr = ($this->relativestart == 2) ? 'before' : 'after';
            $bstr = get_string($bstr, 'availability_relativedate');
            return $nstr . $bstr . userdate($calc, get_string('strftimedatetime', 'langconfig'));
        }
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
            case 2:
                return get_string('dateend', 'availability_relativedate');
            case 3:
                return get_string('dateenrol', 'availability_relativedate');
            default:
                return get_string('datestart', 'availability_relativedate');
        }
    }

    /**
     * Obtains a the options for days week months.
     *
     * @return array
     */
    public static function options_dwm() {
        return [1 => 'days', 2 => 'weeks', 3 => 'months'];
    }

    /**
     * Calculates the date.
     *
     * @param \core_availability\info $info
     * @param int $userid
     * @return int relative date.
     */
    private function calcstart(\core_availability\info $info, $userid) {
        global $DB, $USER;
        $i = 0;
        switch ($this->relativedwm) {
            case 2:
                $i = WEEKSECS;
                break;
            case 3:
                $i = WEEKSECS * 4;
                break;
            default:
                $i = DAYSECS;
                break;
        }
        $i *= $this->relativenumber;
        $course = $info->get_course();
        switch ($this->relativestart) {
            case 2:
                if ($course->enddate > 0) {
                    return $course->enddate - $i;
                } else {
                    return 0;
                }
            case 3:
                if ($userid == 0) {
                    $userid = $USER->id;
                }
                $sql = 'SELECT GREATEST(ue.timestart, ue.timecreated) AS startdate FROM {user_enrolments} ue
                        JOIN {enrol} e on ue.enrolid = e.id WHERE e.courseid = ? AND ue.userid = ? ORDER by startdate DESC';
                if ($lowest = $DB->get_records_sql($sql, [$course->id, $userid])) {
                    $lowest = reset($lowest);
                    return $lowest->startdate + $i;
                }
            default:
                return $course->startdate + $i;
        }
    }
}
