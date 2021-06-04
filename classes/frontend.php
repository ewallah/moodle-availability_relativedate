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
 * Front-end class.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Gets additional parameters for the plugin's initInner function.
     *
     * Default returns no parameters.
     *
     * @param \stdClass $course Course object
     * @param \cm_info $cm Course-module currently being edited (null if none)
     * @param \section_info $section Section currently being edited (null if none)
     * @return array Array of parameters for the JavaScript function
     */
    protected function get_javascript_init_params($course, \cm_info $cm = null, \section_info $section = null) {
        global $DB;
        $optionsdwm = self::convert_associative_array_for_js(condition::options_dwm(), 'field', 'display');
        $optionsstart = [(object)['field' => 1, 'display' => condition::options_start(1)],
                         (object)['field' => 2, 'display' => condition::options_start(2)],
                         (object)['field' => 3, 'display' => condition::options_start(3)]];
        $cond = 'courseid = :courseid AND enrolenddate > 0';
        if ($cnt = $DB->count_records_select('enrol', $cond, ['courseid' => $course->id])) {
            if ($cnt > 0) {
                $optionsstart[3] = (object)['field' => 4, 'display' => condition::options_start(4)];
            }
        }
        $warnings = [];
        if ($course->enddate == 0) {
            $warnings[] = get_string('noenddate', 'availability_relativedate');
        }
        return [$optionsdwm, $optionsstart, is_null($section), $warnings];
    }
}
