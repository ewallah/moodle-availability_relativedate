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
 * Unit tests for the relativedate condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_relativedate\condition;

/**
 * Unit tests for the relativedate condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass availability_relativedate
 */
class availability_relativedate_testcase extends advanced_testcase {

    /**
     * Load required classes.
     */
    public function setUp():void {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_condition.php');
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * Tests constructing and using relative date condition as part of tree.
     * @coversDefaultClass availability_relativedate\condition
     */
    public function test_in_tree() {
        global $DB;
        $this->resetAfterTest();
        $this->setTimezone('UTC');

        set_config('enableavailability', true);
        $dg = $this->getDataGenerator();

        $stru1 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]]];
        $stru2 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]]];
        $stru3 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 3]]];
        $stru4 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 4, 'd' => 4, 's' => 4]]];
        $stru5 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 5, 'd' => 4, 's' => 4]]];
        $stru6 = (object)['op' => '|', 'show' => false,
            'c' => [(object)['type' => 'relativedate', 'n' => 5, 'd' => 5, 's' => 5]]];
        $tree1 = new \core_availability\tree($stru1);
        $tree2 = new \core_availability\tree($stru2);
        $tree3 = new \core_availability\tree($stru3);
        $tree4 = new \core_availability\tree($stru4);
        $tree5 = new \core_availability\tree($stru5);
        $tree6 = new \core_availability\tree($stru6);

        $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $now = time();
        $course = $dg->create_course(['startdate' => $now, 'enddate' => $now + 7 * WEEKSECS]);
        $user = $dg->create_user(['timezone' => 'UTC']);
        $dg->enrol_user($user->id, $course->id, $studentroleid);

        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $DB->set_field('enrol', 'enrolenddate', $now + 1000, ['id' => $instance->id]);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $user = $dg->create_user(['timezone' => 'UTC']);
        $selfplugin->enrol_user($instance, $user->id, $studentroleid, $now);

        $this->setUser($user);
        $info = new \core_availability\mock_info($course, $user->id);
        list($sql, $params) = $tree1->get_user_list_sql(false, $info, false);
        $this->assertEquals('', $sql);
        $this->assertEquals([], $params);
        $strf = get_string('strftimedatetime', 'langconfig');
        $nau = 'Not available unless:';
        // 1 Hour after course start date.
        $calc = userdate(strtotime("+1 hour", $course->startdate), $strf, 0);
        $this->assertEquals("$nau From $calc", $tree1->get_full_information($info));
        // 2 Days before course end date.
        $calc = userdate(strtotime("-2 day", $course->enddate), $strf);
        $this->assertEquals("$nau Until $calc", $tree2->get_full_information($info));
        // 3 Weeks after user enrolment day.
        $calc = userdate(strtotime("+3 week", $course->startdate), $strf);
        $this->assertEquals("$nau From $calc", $tree3->get_full_information($info));
        // 4 Months after enrolment end date.
        $calc = userdate(strtotime("+4 month", $now + 1000), $strf);
        $this->assertEquals("$nau From $calc", $tree4->get_full_information($info));
        // 5 Months after enrolment end date.
        $calc = userdate(strtotime("+5 month", $now + 1000), $strf);
        $this->assertEquals("$nau From $calc", $tree5->get_full_information($info));
        $this->assertFalse($tree1->is_available_for_all());
        $this->assertFalse($tree2->is_available_for_all());
        $this->assertFalse($tree3->is_available_for_all());
        $this->assertFalse($tree4->is_available_for_all());
        $this->assertFalse($tree5->is_available_for_all());
        $this->assertFalse($tree6->is_available_for_all());
        $this->assertFalse($tree1->check_available(false, $info, false, 0)->is_available());
        $this->assertFalse($tree1->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree3->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree4->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree5->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree6->check_available(false, $info, false, $user->id)->is_available());
        $this->assertTrue($tree1->check_available(true, $info, false, 0)->is_available());
        $this->assertTrue($tree1->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree3->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree4->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree5->check_available(true, $info, false, $user->id)->is_available());
        $this->assertFalse($tree6->check_available(true, $info, false, $user->id)->is_available());
    }

    /**
     * Tests the constructor including error conditions.
     * @covers availability_relativedate\condition
     */
    public function test_constructor() {
        $structure = (object)['type' => 'relativedate'];
        $cond = new condition($structure);
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
        $structure->n = 1;
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
        $cond = new condition($structure);
        $structure->d = 1;
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
        $cond = new condition($structure);
        $structure->d = '2';
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
        $cond = new condition($structure);
        $structure->n = 'a';
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
        $cond = new condition($structure);
        $structure->e = 'a';
        $cond = new condition($structure);
        $this->assertNotEqualsCanonicalizing($structure, $cond->save());
    }

    /**
     * Tests the save() function.
     * @covers availability_relativedate\condition
     */
    public function test_save() {
        $structure = (object)['n' => 1, 'd' => 2, 's' => 1];
        $cond = new condition($structure);
        $structure->type = 'relativedate';
        $this->assertEqualsCanonicalizing($structure, $cond->save());
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers availability_relativedate\condition
     */
    public function test_get_description() {
        global $DB;
        $this->resetAfterTest();
        $dg = $this->getDataGenerator();
        $now = time();
        $course = $dg->create_course(['startdate' => $now, 'enddate' => $now + 7 * WEEKSECS]);
        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $DB->set_field('enrol', 'enrolenddate', $now + 1000, ['id' => $instance->id]);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $user = $dg->create_user();
        $selfplugin->enrol_user($instance, $user->id, 5, $now);
        $info = new \core_availability\mock_info($course, $user->id);
        $this->setUser($user);
        $strf = get_string('strftimedatetime', 'langconfig');
        $nau = 'Not available unless:';

        // Course start date.
        $cond = new condition((object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]);
        $calc = userdate(strtotime("+1 hour", $now), $strf);
        $this->assertEquals("From $calc", $cond->get_description(true, false, $info));
        $this->assertEquals("Until $calc", $cond->get_description(true, true, $info));
        $this->assertEquals("$nau From $calc", $cond->get_standalone_description(false, false, $info));
        $this->assertEquals("$nau Until $calc", $cond->get_standalone_description(false, true, $info));

        // Course end date.
        $cond = new condition((object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]);
        $calc = userdate(strtotime("-2 day", $course->enddate), $strf);
        $this->assertEquals("Until $calc", $cond->get_description(true, false, $info));
        $this->assertEquals("From $calc", $cond->get_description(true, true, $info));
        $this->assertEquals("$nau Until $calc", $cond->get_standalone_description(false, false, $info));
        $this->assertEquals("$nau From $calc", $cond->get_standalone_description(false, true, $info));

        // Enrolment start date.
        $cond = new condition((object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 3]);
        $calc = userdate(strtotime("+3 week", $now), $strf);
        $this->assertEquals("From $calc", $cond->get_description(true, false, $info));
        $this->assertEquals("Until $calc", $cond->get_description(true, true, $info));
        $this->assertEquals("$nau From $calc", $cond->get_standalone_description(false, false, $info));
        $this->assertEquals("$nau Until $calc", $cond->get_standalone_description(false, true, $info));

        // Enrolment end date.
        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $DB->set_field('enrol', 'enrolenddate', $now + 1000, ['id' => $instance->id]);
        $cond = new condition((object)['type' => 'relativedate', 'n' => 4, 'd' => 4, 's' => 4]);
        $calc = userdate(strtotime("+4 month", $now + 1000), $strf);
        $this->assertEquals("From $calc", $cond->get_description(true, false, $info));
        $this->assertEquals("Until $calc", $cond->get_description(true, true, $info));
        $this->assertEquals("$nau From $calc", $cond->get_standalone_description(false, false, $info));
        $this->assertEquals("$nau Until $calc", $cond->get_standalone_description(false, true, $info));
    }

    /**
     * Tests a course with no enddate.
     * @covers availability_relativedate\condition
     */
    public function test_noenddate() {
        global $DB, $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', true);
        $dg = $this->getDataGenerator();
        $now = time();
        $course1 = $dg->create_course();
        $course2 = $dg->create_course(['enddate' => $now + 14 * WEEKSECS]);
        $user = $dg->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
        $dg->enrol_user($user->id, $course1->id, $roleid);
        $dg->enrol_user($user->id, $course2->id, $roleid);
        $page1 = $dg->get_plugin_generator('mod_page')->create_instance(['course' => $course1]);
        $page2 = $dg->get_plugin_generator('mod_page')->create_instance(['course' => $course2]);
        $modinfo1 = get_fast_modinfo($course1);
        $modinfo2 = get_fast_modinfo($course2);
        $cm1 = $modinfo1->get_cm($page1->cmid);
        $cm2 = $modinfo2->get_cm($page2->cmid);
        $info = new \core_availability\info_module($cm1);
        $cond = new condition((object)['type' => 'relativedate', 'n' => 7, 'd' => 2, 's' => 2]);
        $information = $cond->get_description(true, false, $info);
        $this->assertEquals('This course has no end date', $information);
        $this->assertEquals('{relativedate: 7 days before course end date}', "$cond");
        // No enddate => Never available.
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, false, $user->id));
        $info = new \core_availability\info_module($cm2);
        $information = $cond->get_description(true, false, $info);
        $strf = get_string('strftimedatetime', 'langconfig');
        $this->assertStringNotContainsString('(No course enddate)', $information);
        $str = userdate($course2->enddate - (7 * 24 * 3600), $strf);
        $this->assertEquals("Until $str (7 days before course end date)", $information);
        $this->assertEquals('{relativedate: 7 days before course end date}', "$cond");
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $this->assertTrue($cond->is_available(true, $info, false, $user->id));
        $this->assertFalse($cond->is_available(false, $info, false, null));
        $this->assertTrue($cond->is_available(true, $info, false, null));

        $cond = new condition((object)['type' => 'relativedate', 'n' => 7, 'd' => 2, 's' => 3]);
        $information = $cond->get_description(true, false, $info);
        $this->assertEquals('(7 days after user enrolment date)', $information);
        $this->assertFalse($cond->is_available(false, $info, false, $USER->id));
        $this->assertFalse($cond->is_available(true, $info, false, $USER->id));

        $cond = new condition((object)['type' => 'relativedate', 'n' => 7, 'd' => 2, 's' => 4]);
        $information = $cond->get_description(false, false, $info);
        $this->assertEquals('(7 days after enrolment method end date)', $information);
    }

    /**
     * Tests a reflection.
     * @covers availability_relativedate\condition
     */
    public function test_reflection() {
        global $USER;
        $this->resetAfterTest();
        $this->setAdminUser();
        $dg = $this->getDataGenerator();
        $now = time();
        $course = $dg->create_course(['startdate' => $now, 'enddate' => $now + 1000]);
        $cond = new condition((object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]);
        $condition = new \availability_relativedate\condition($cond);
        $name = 'availability_relativedate\condition';
        $result = phpunit_util::call_internal_method($condition, 'get_debug_string', [], $name);
        $this->assertEquals(' 1 days after course start date', $result);
        $result = phpunit_util::call_internal_method($condition, 'calc', [$course, $USER->id], $name);
        $this->assertEquals($now + 24 * 3600, $result);
    }

    /**
     * Tests static methods.
     * @covers availability_relativedate\condition
     */
    public function test_static() {
        $this->assertCount(4, \availability_relativedate\condition::options_dwm());
        $this->assertEquals('hour', \availability_relativedate\condition::option_dwm(1));
        $this->assertEquals('after course start date', \availability_relativedate\condition::options_start(1));
        $this->assertEquals('before course end date', \availability_relativedate\condition::options_start(2));
        $this->assertEquals('after user enrolment date', \availability_relativedate\condition::options_start(3));
        $this->assertEquals('after enrolment method end date', \availability_relativedate\condition::options_start(4));
        $this->assertEquals('', \availability_relativedate\condition::options_start(5));
    }
}
