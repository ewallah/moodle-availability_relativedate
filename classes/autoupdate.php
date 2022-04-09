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
 * Autoupdate for handling deleted course modules
 *
 * @package   availability_relativedate
 * @copyright 2022 Stefan Hanauska <stefan.hanauska@altmuehlnet.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_relativedate;

/**
 * relativedate from course start condition.
 *
 * @package   availability_relativedate
 * @copyright 2019 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class autoupdate {
    /**
     * Called when a course_module_deleted event is triggered. Updates the completion state for all
     * learningmaps in the course of the activity.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function update_from_event(\core\event\base $event) : void {
        $data = $event->get_data();
        if (isset($data['courseid']) && $data['courseid'] > 0) {
            $modinfo = get_fast_modinfo($data['courseid']);
            if (condition::completion_value_used($data['courseid'], $data['objectid'])) {
                \core_availability\info::update_dependency_id_across_course(
                    $data['courseid'],
                    'course_modules',
                    $data['objectid'],
                    -1
                );
            }
        }
    }
}
