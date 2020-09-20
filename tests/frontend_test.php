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
 * Unit tests for frontend of relativedate condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_relativedate\frontend;

/**
 * Unit tests for frontend of relativedate condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \availability_relativedate\condition
 */
class availability_relativedate_front_testcase extends \advanced_testcase {

    /**
     * Tests using relativedate condition in front end.
     * @covers availability_relativedate\frontend
     */
    public function test_frontend() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $enabled = enrol_get_plugins(true);
        $enabled['self'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
        $dg = $this->getDataGenerator();
        $course = $dg->create_course();
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $DB->set_field('enrol', 'enrolenddate', time() + 10000, ['id' => $instance->id]);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $user = $dg->create_user();
        $selfplugin->enrol_user($instance, $user->id);

        $frontend = new \availability_relativedate\frontend();
        $class = new \ReflectionClass('availability_relativedate\frontend');
        $method = $class->getMethod('get_javascript_strings');
        $method->setAccessible(true);
        $this->assertEquals([], $method->invokeArgs($frontend, []));
        $method = $class->getMethod('get_javascript_init_params');
        $method->setAccessible(true);
        $this->assertEquals(4, count($method->invokeArgs($frontend, [$course])));
        $method = $class->getMethod('allow_add');
        $method->setAccessible(true);

        $this->assertTrue($method->invokeArgs($frontend, [$course]));
        $this->assertFalse($method->invokeArgs($frontend, [$course, null, $sections[0]]));
        $this->assertTrue($method->invokeArgs($frontend, [$course, null, $sections[1]]));
    }

}