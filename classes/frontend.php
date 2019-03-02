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
        $options = [];
        foreach (condition::options_dwm() as $key => $value) {
            $options[$key] = strtolower(get_string($value));
        };
        $options = self::convert_associative_array_for_js($options, 'field', 'display');
        $aptions = [1 => condition::options_start(1), 2 => condition::options_start(2), 3 => condition::options_start(3)];
        $aptions = self::convert_associative_array_for_js($aptions, 'field', 'display');
        return [$options, $aptions];
    }
}
