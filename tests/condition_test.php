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
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_condition.php');
        require_once($CFG->libdir . '/completionlib.php');
    }

    /**
     * Tests constructing and using relative date condition as part of tree.
     */
    public function test_in_tree() {
        global $CFG, $DB;
        $this->resetAfterTest();

        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        
        $stru1 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]]];
        $stru2 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]]];
        $stru3 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 3]]];
        $stru4 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 4, 'd' => 4, 's' => 3]]];
        $stru5 = (object)['op' => '|', 'show' => true,
            'c' => [(object)['type' => 'relativedate', 'n' => 5, 'd' => 5, 's' => 5]]];
        $tree1 = new \core_availability\tree($stru1);
        $tree2 = new \core_availability\tree($stru2);
        $tree3 = new \core_availability\tree($stru3);
        $tree4 = new \core_availability\tree($stru4);
        $tree5 = new \core_availability\tree($stru5);

        $course = $generator->create_course(['startdate' => time(), 'enddate' => time() + 7 * WEEKSECS]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
        $info = new \core_availability\mock_info($course, $user->id);

        list($sql, $params) = $tree1->get_user_list_sql(false, $info, false);
        $this->assertEquals('', $sql);
        $this->assertEquals([], $params);
        $this->assertContains('From 1 hour after', $tree1->get_full_information($info));
        $this->assertContains('Until 2 days before course end date', $tree2->get_full_information($info));
        $this->assertContains('From 3 weeks after user enrolment date', $tree3->get_full_information($info));
        $this->assertContains('From 4 months after user enrolment date', $tree4->get_full_information($info));
        $this->assertEquals('Not available unless: ', $tree5->get_full_information($info));
        $this->assertFalse($tree1->is_available_for_all());
        $this->assertFalse($tree2->is_available_for_all());
        $this->assertFalse($tree3->is_available_for_all());
        $this->assertFalse($tree4->is_available_for_all());
        $this->assertFalse($tree5->is_available_for_all());
        $this->assertFalse($tree1->check_available(false, $info, false, 0)->is_available());
        $this->assertFalse($tree1->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree2->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree3->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree4->check_available(false, $info, false, $user->id)->is_available());
        $this->assertFalse($tree5->check_available(false, $info, false, $user->id)->is_available());
        $this->assertTrue($tree1->check_available(true, $info, false, 0)->is_available());
        $this->assertTrue($tree1->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree2->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree3->check_available(true, $info, false, $user->id)->is_available());
        $this->assertTrue($tree4->check_available(true, $info, false, $user->id)->is_available());
        $this->assertFalse($tree5->check_available(true, $info, false, $user->id)->is_available());
    }

    /**
     * Tests the constructor including error conditions.
     * @covers availability_relativedate\condition
     */
    public function test_constructor() {
        $structure = (object)['type' => 'relativedate'];
        $condc = new condition($structure);
        $structure->n = 1;
        $condc = new condition($structure);

        $structure->d = 1;
        $condc = new condition($structure);

        $structure->d = '2';
        $condc = new condition($structure);

        $structure->n = 'a';
        $condc = new condition($structure);

        $structure->e = 'a';
        $condc = new condition($structure);
    }

    /**
     * Tests the save() function.
     * @covers availability_relativedate\condition
     */
    public function test_save() {
        $structure = (object)['n' => 1, 'd' => 2, 's' => 1];
        $cond = new condition($structure);
        $structure->type = 'relativedate';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers availability_relativedate\condition
     */
    public function test_get_description() {
        global $DB;
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(['startdate' => time(), 'enddate' => time() + 7 * WEEKSECS]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
        $info = new \core_availability\mock_info($course, $user->id);
        $this->setUser($user);
        $cond = new condition((object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]);
        $this->assertContains('1 hour after course start date', $cond->get_description(true, false, $info));
        $this->assertContains('Until 1 hour after course start date', $cond->get_description(true, true, $info));
        $this->assertContains('1 hour after course start date',
            $cond->get_standalone_description(false, false, $info));
        $this->assertContains('Until 1 hour after course start date',
            $cond->get_standalone_description(false, true, $info));

        $cond = new condition((object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]);
        $this->assertEquals('Not available unless: Until 2 days before course end date',
            $cond->get_standalone_description(false, false, $info));
        $cond = new condition((object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 2]);
        $this->assertEquals('Not available unless: Until 3 weeks before course end date',
            $cond->get_standalone_description(false, false, $info));
        $cond = new condition((object)['type' => 'relativedate', 'n' => 4, 'd' => 4, 's' => 3]);
        $this->assertContains('From ', $cond->get_description(true, false, $info));
        $this->assertContains('Until ', $cond->get_description(true, true, $info));
        $this->assertContains('From ', $cond->get_standalone_description(false, false, $info));
        $this->assertContains('Until ', $cond->get_standalone_description(false, true, $info));

        $cond = new condition((object)['type' => 'relativedate', 'n' => 9, 'd' => 9, 's' => 9]);
        $this->assertEquals('', $cond->get_description(true, false, $info));
        $this->assertEquals('', $cond->get_description(true, true, $info));
        $this->assertEquals('Not available unless: From ', $cond->get_standalone_description(false, false, $info));
        $this->assertEquals('Not available unless: Until ', $cond->get_standalone_description(false, true, $info));
    }

    /**
     * Tests a course with no enddate.
     * @covers availability_relativedate\condition
     */
    public function test_noenddate() {
        global $CFG, $DB, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course(['enddate' => time() + 14 * WEEKSECS]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course1->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
        $generator->enrol_user($user->id, $course2->id, $DB->get_field('role', 'id', ['shortname' => 'student']));
        $page1 = $generator->get_plugin_generator('mod_page')->create_instance(['course' => $course1]);
        $page2 = $generator->get_plugin_generator('mod_page')->create_instance(['course' => $course2]);
        $modinfo1 = get_fast_modinfo($course1);
        $modinfo2 = get_fast_modinfo($course2);
        $cm1 = $modinfo1->get_cm($page1->cmid);
        $cm2 = $modinfo2->get_cm($page2->cmid);
        $PAGE->set_url('/course/modedit.php', ['update' => $page1->cmid]);
        \core_availability\frontend::include_all_javascript($course1, $cm1);
        $info = new \core_availability\info_module($cm1);
        $cond = new condition((object)['type' => 'relativedate', 'n' => 7, 'd' => 2, 's' => 2]);
        $information = $cond->get_description(true, false, $info);
        $this->assertEquals('This course has no end date', $information);
        $this->assertEquals('{relativedate: 7 days before course end date}', "$cond");
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, false, $user->id));

        $PAGE->set_url('/course/modedit.php', ['update' => $page2->cmid]);
        \core_availability\frontend::include_all_javascript($course2, $cm2);
        $info = new \core_availability\info_module($cm2);
        $information = $cond->get_description(true, false, $info);
        $this->assertContains('7 days before course end date', $information);
        $this->assertNotContains('(No course enddate)', $information);
        $this->assertEquals('{relativedate: 7 days before course end date}', "$cond");
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $this->assertTrue($cond->is_available(true, $info, false, $user->id));
        $this->assertFalse($cond->is_available(false, $info, false, null));
        $this->assertTrue($cond->is_available(true, $info, false, null));
    }

    /**
     * Tests using relativedate condition in front end.
     * @covers availability_relativedate\frontend
     */
    public function test_frontend() {
        global $CFG;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        $generator->enrol_user($user->id, $course->id);

        $frontend = new availability_relativedate\frontend();
        $class = new ReflectionClass('availability_relativedate\frontend');
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

    /**
     * Test privacy.
     * @covers availability_relativedate\privacy\provider
     */
    public function test_privacy() {
        $privacy = new availability_relativedate\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }
}