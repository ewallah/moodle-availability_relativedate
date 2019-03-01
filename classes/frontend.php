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
 * @copyright 2019 Renaat Debleu (info@eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

defined('MOODLE_INTERNAL') || die();

/**
 * Front-end class.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu (info@eWallah.net)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

    /**
     * Gets a list of string identifiers (in the plugin's language file) that
     * are required in JavaScript for this plugin.
     *
     * @return array Array of required string identifiers
     */
    protected function get_javascript_strings() {
        return [];
    }

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
        $a = get_string('after', 'availability_relativedate');
        $b = get_string('before', 'availability_relativedate');
        $options = [1 => get_string('days'), 2 => get_string('weeks'), 3 => strtolower(get_string('months'))];
        $options = self::convert_associative_array_for_js($options, 'field', 'display');
        $options2 = [
            1 => $a . ' ' . strtolower(get_string('startdate')),
            2 => $b . ' ' . strtolower(get_string('enddate')),
            3 => get_string('dateenrol', 'availability_relativedate')];
        $options2 = self::convert_associative_array_for_js($options2, 'field', 'display');
        return [$options, $options2];
    }
}
