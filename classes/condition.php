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

    /** @var int relativedate Calculated date. */
    private $relativedate;

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
        $allow = time() >= $this->calcstart();
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
        $calc = $this->calcstart();
        // $strd = get_string('direction_from', 'availability_date');
        $dirstr = get_string('direction_from', 'availability_date');
        $nstr = $not ? 'Not ' : '';
        $dstr = ' (' . userdate($this->relativedate, get_string('strftimedatetime', 'langconfig')) . ')';
        if ($full) {
            switch ($this->relativedwm) {
                case 2:
                    $str = get_string('weeks');
                    break;
                case 3:
                    $str = get_string('months');
                    break;
                default:
                    $str = get_string('days');
                    break;
            }
            $str = $nstr . $this->relativenumber . ' ' . strtolower($str);
            switch ($this->relativestart) {
                case 2:
                    return $str . get_string('before', 'availability_relativedate') . strtolower(get_string('enddate')) . $dstr;
                case 3:
                    return $str . get_string('after', 'availability_relativedate') .
                        get_string('dateenrol', 'availability_relativedate') . $dstr;
                default:
                    return $str . get_string('after', 'availability_relativedate') . strtolower(get_string('startdate')) . $dstr;
            }
        } else {
            $bstr = ($this->relativestart == 2) ? 'before' : 'after';
            return $nstr . ' ' . $bstr . ' ' . userdate($this->relativedate, get_string('strftimedatetime', 'langconfig'));
        }
    }

    /**
     * Obtains a string describing this restriction, used when there is only
     * a single restriction to display. (I.e. this provides a 'short form'
     * rather than showing in a list.)
     *
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on his item
     */
    public function get_standalone_description($full, $not, \core_availability\info $info) {
        return $this->get_description($full, $not, $info);
    }

    /**
     * Obtains a representation of the options of this condition as a string for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return $this->relativenumber;
    }

    /**
     * Calculates the date.
     *
     * @return int relative date.
     */
    private function calcstart() {
        global $COURSE, $DB;
        if ($this->relativedate == 0) {
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
            switch ($this->relativestart) {
                case 2:
                    $this->relativedate = $COURSE->enddate - $i;
                    break;
                case 3:
                    $this->relativedate = time() + $i;
                    break;
                default:
                    $this->relativedate = $COURSE->startdate + $i;
                    break;
            }
        }
        return $this->relativedate;
    }
}
