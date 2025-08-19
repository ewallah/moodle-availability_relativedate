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
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

use cm_info;
use section_info;
use stdClass;

/**
 * Front-end class.
 *
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Get JavaScript initialization parameters.
     *
     * @param stdClass $course The course object.
     * @param cm_info|null $cm The course module info.
     * @param section_info|null $section The section info.
     * @return array The JavaScript initialization parameters.
     */
    protected function get_javascript_init_params($course, ?cm_info $cm = null, ?section_info $section = null) {
        global $DB;

        // Convert associative array for JS.
        $optionsdwm = self::convert_associative_array_for_js(condition::options_dwm(), 'field', 'display');

        // Initialize start options.
        $optionsstart = [
            ['field' => 1, 'display' => condition::options_start(1)],
            ['field' => 6, 'display' => condition::options_start(6)],
        ];

        // Add course end date dependent options.
        if ($course->enddate != 0) {
            $optionsstart[] = ['field' => 5, 'display' => condition::options_start(5)];
            $optionsstart[] = ['field' => 2, 'display' => condition::options_start(2)];
        }

        // Add additional start options.
        $optionsstart[] = ['field' => 3, 'display' => condition::options_start(3)];

        // Check if the course has enrolments with end dates.
        if ($DB->count_records_select('enrol', 'courseid = :courseid AND enrolenddate > 0', ['courseid' => $course->id]) > 0) {
            $optionsstart[] = ['field' => 4, 'display' => condition::options_start(4)];
        }

        // Initialize activity selection array.
        $activitysel = [];
        if ($course->enablecompletion) {
            $modinfo = get_fast_modinfo($course);
            $str = get_string('section');
            $enabled = false;

            // Get sections with content.
            foreach ($modinfo->get_sections() as $sectionnum => $cursection) {
                $sectioninfo = $modinfo->get_section_info($sectionnum);
                $sectionname = empty($sectioninfo->name) ? "$str $sectionnum" : format_string($sectioninfo->name);
                $sectionmodules = [];

                // Get course modules in the section.
                foreach ($cursection as $cmid) {
                    if ($cm && $cm->id === $cmid) {
                        continue;
                    }

                    $module = $modinfo->get_cm($cmid);

                    // Get only course modules which are not being deleted.
                    if ($module->deletioninprogress == 0) {
                        $completionenabled = ($module->completion > 0);
                        $sectionmodules[] = [
                            'id' => $cmid,
                            'name' => format_string($module->name),
                            'completionenabled' => $completionenabled,
                        ];
                        $enabled = $completionenabled ? true : $enabled;
                    }
                }

                $activitysel[] = [
                    'name' => $sectionname,
                    'coursemodules' => $sectionmodules,
                ];
            }

            // Add activity completion start option if any activity has completion enabled.
            if ($enabled) {
                $optionsstart[] = ['field' => 7, 'display' => condition::options_start(7)];
            }
        }
        $config = get_config('availability_relativedate');
        $max = property_exists($config, 'maxnumber') ? $config->maxnumber + 1 : 60;
        $default = property_exists($config, 'defaultnumber') ? $config->defaultnumber : 1;
        $dwm = property_exists($config, 'defaultdwm') ? $config->defaultdwm : 2;
        $start = property_exists($config, 'defaultstart') ? $config->defaultstart : 1;
        $arr = [$max, $default, $dwm, $start];
        return [$optionsdwm, $optionsstart, is_null($section), [], $activitysel, $arr];
    }
}
