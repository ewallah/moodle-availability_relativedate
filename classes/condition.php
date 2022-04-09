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
 * @package   availability_relativedate
 * @copyright 2019 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

use context_course;
use core_availability\info;
use stdClass;

/**
 * relativedate from course start condition.
 *
 * @package   availability_relativedate
 * @copyright 2019 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * 5 => After last visit
     * 6 => After completion of an activity
     */
    private $relativestart;

    /**
     * @var int Course module id of the activity used by type 6
     */
    private $relativecoursemodule;

    /** @var array Array of modules used in these conditions for course */
    protected static $modsusedincondition = [];

    /**
     * Constructor.
     *
     * @param stdClass $structure Data structure from JSON decode
     */
    public function __construct($structure) {
        $this->relativenumber = property_exists($structure, 'n') ? (int)$structure->n : 1;
        $this->relativedwm = property_exists($structure, 'd') ? (int)$structure->d : 2;
        $this->relativestart = property_exists($structure, 's') ? (int)$structure->s : 1;
        $this->relativecoursemodule = property_exists($structure, 'c') ? (int)$structure->c : 0;
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
            's' => intval($this->relativestart),
            'c' => intval($this->relativecoursemodule)
        ];
    }

    /**
     * Determines whether this particular item is currently available.
     *
     * @param bool $not
     * @param info $info
     * @param bool $grabthelot
     * @param int $userid If set, specifies a different user ID to check availability for
     * @return bool True if this item is available to the user, false otherwise
     */
    public function is_available($not, info $info, $grabthelot, $userid) {
        $calc = $this->calc($info->get_course(), $userid);
        if ($calc === 0) {
            // Always not available if for some reason the value could not be calculated.
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
    public function get_description($full, $not, info $info): string {
        global $USER;
        $course = $info->get_course();
        $context = context_course::instance($course->id);
        $capability = has_capability('moodle/course:manageactivities', $context);
        if ($this->relativestart === 2) {
            if ((!isset($course->enddate) || $course->enddate == 0) && $capability) {
                return get_string('noenddate', 'availability_relativedate');
            }
            $frut = $not ? 'from' : 'until';
        } else {
            $frut = $not ? 'until' : 'from';
        }
        $calc = $this->calc($course, $USER->id);
        if ($calc == 0) {
            return '('. trim($this->get_debug_string()) . ')';
        }
        $a = new stdClass();
        $a->rnumber = userdate($calc, get_string('strftimedatetime', 'langconfig'));
        $a->rtime = ($capability && $full) ? '('. trim($this->get_debug_string()) . ')' : '';
        $a->rela = '';
        return trim(get_string($frut, 'availability_relativedate', $a));
    }

    /**
     * Obtains a representation of the options of this condition as a string for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        $modname = '';
        if ($this->relativestart == 6) {
            if (!get_coursemodule_from_id('', $this->relativecoursemodule)) {
                return 0;
            }
            $modname = ' ' . \core_availability\condition::description_cm_name($this->relativecoursemodule);
        }
        return ' ' . $this->relativenumber . ' ' . self::options_dwm()[$this->relativedwm] . ' ' .
               self::options_start($this->relativestart) . $modname;
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
            case 5:
                return get_string('datelastvisit', 'availability_relativedate');
            case 6:
                return get_string('datecompletion', 'availability_relativedate');
        }
        return '';
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
    public static function option_dwm(int $i): string {
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
    private function calc($course, $userid): int {
        $x = $this->option_dwm($this->relativedwm);
        if ($this->relativestart == 1) {
            // Course start date.
            return $this->fixdate("+$this->relativenumber $x", $course->startdate);
        } else if ($this->relativestart == 2 && isset($course->enddate) && $course->enddate != 0) {
            // Course end date.
            return $this->fixdate("-$this->relativenumber $x", $course->enddate);
        } else if ($this->relativestart == 3) {
            // Latest enrolment date.
            $sql = 'SELECT ue.timestart
                    FROM {user_enrolments} ue
                    JOIN {enrol} e on ue.enrolid = e.id
                    WHERE e.courseid = :courseid AND ue.userid = :userid AND ue.timestart > 0
                    ORDER by ue.timestart DESC';
            $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
            if ($lowest == 0) {
                // A teacher or admin without restriction - or a student with no limit set?
                $sql = 'SELECT ue.timecreated
                        FROM {user_enrolments} ue
                        JOIN {enrol} e on (e.id = ue.enrolid AND e.courseid = :courseid)
                        WHERE ue.userid = :userid
                        ORDER by ue.timecreated DESC';
                $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
            }
            if ($lowest > 0) {
                return $this->fixdate("+$this->relativenumber $x", $lowest);
            }
        } else if ($this->relativestart == 4) {
            // Latest enrolment end date.
            $sql = 'SELECT e.enrolenddate
                    FROM {user_enrolments} ue
                    JOIN {enrol} e on ue.enrolid = e.id
                    WHERE e.courseid = :courseid AND ue.userid = :userid
                    ORDER by e.enrolenddate DESC';
            $lowest = $this->getlowest($sql, ['courseid' => $course->id, 'userid' => $userid]);
            if ($lowest > 0) {
                return $this->fixdate("+$this->relativenumber $x", $lowest);
            }
        } else if ($this->relativestart == 5) {
            global $USER;
            if ($USER->id == $userid) {
                $conditionuser = $USER;
            } else {
                $conditionuser = \core_user::get_user($userid);
            }
            if (isset($conditionuser->lastcourseaccess[$course->id])) {
                $lastaccess = $conditionuser->lastcourseaccess[$course->id];
            } else {
                $lastaccess = 0;
            }
            return $this->fixdate("+$this->relativenumber $x", $lastaccess);
        } else if ($this->relativestart == 6) {
            $cm = new stdClass;
            $cm->id = $this->relativecoursemodule;
            $cm->course = $course->id;
            $cminfo = get_fast_modinfo($course);
            try {
                $cminfo->get_cm($this->relativecoursemodule);
            } catch (\Exception $e) {
                return 0;
            }
            $completion = new \completion_info($course);
            $completiondata = $completion->get_data($cm);
            if ($completiondata->completionstate > 0) {
                return $this->fixdate("+$this->relativenumber $x", $completiondata->timemodified);
            } else {
                return 0;
            }
        }
        return 0;
    }

    /**
     * Get the record with the lowest value.
     *
     * @param string $sql
     * @param array $parameters
     * @return int lowest value.
     */
    private function getlowest($sql, $parameters): int {
        global $DB;
        if ($lowestrec = $DB->get_record_sql($sql, $parameters, IGNORE_MULTIPLE)) {
            return reset($lowestrec);
        }
        return 0;
    }


    /**
     * Keep the original hour.
     *
     * @param string $calc
     * @param int $newdate
     * @return int relative date.
     */
    private function fixdate($calc, $newdate): int {
        $olddate = strtotime($calc, $newdate);
        if ($this->relativedwm > 1) {
            $arr1 = getdate($olddate);
            $arr2 = getdate($newdate);
            return mktime($arr2['hours'], $arr2['minutes'], $arr2['seconds'], $arr1['mon'], $arr1['mday'], $arr1['year']);
        }
        return $olddate;
    }

    /**
     * This function returns true if a restriction in the course is based on the completion value
     * of a certain activity. This is used for handling of caching.
     *
     * @param stdClass $course course object
     * @param int $cmid id of the course module
     * @return boolean true if availability is dependent on this course module
     */
    public static function completion_value_used($course, $cmid): bool {
        if (!array_key_exists($course->id, self::$modsusedincondition)) {
            $modinfo = get_fast_modinfo($course);
            self::$modsusedincondition[$course->id] = [];

            foreach ($modinfo->cms as $othercm) {
                if (is_null($othercm->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_module($othercm);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_relativedate\condition') as $cond) {
                    $condcmid = $cond->get_cmid();
                    if (!empty($condcmid)) {
                        self::$modsusedincondition[$course->id][$condcmid] = true;
                    }
                }
            }

            foreach ($modinfo->get_section_info_all() as $section) {
                if (is_null($section->availability)) {
                    continue;
                }
                $ci = new \core_availability\info_section($section);
                $tree = $ci->get_availability_tree();
                foreach ($tree->get_all_children('availability_relativedate\condition') as $cond) {
                    $condcmid = $cond->get_cmid();
                    if (!empty($condcmid)) {
                        self::$modsusedincondition[$course->id][$condcmid] = true;
                    }
                }
            }
        }
        return array_key_exists($cmid, self::$modsusedincondition[$course->id]);
    }

    /**
     * Get the cmid referenced in the access restriction.
     *
     * @return int cmid of the referenced cm
     */
    public function get_cmid(): int {
        return $this->relativecoursemodule;
    }
}
