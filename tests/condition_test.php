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
 * @copyright 2019 Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_relativedate\condition;

/**
 * Unit tests for the relativedate condition.
 *
 * @package availability_relativedate
 * @copyright 2019 Renaat Debleu (info@eWallah.net)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
     * Tests constructing and using relativedate condition as part of tree.
     */
    public function test_in_tree() {
        global $CFG;
        $this->resetAfterTest();

        // Create course with relativedate turned on.
        $CFG->enableavailability = true;
        $course = $this->getDataGenerator()->create_course();
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $info = new \core_availability\mock_info($course, $user->id);

        $stru1 = (object)['op' => '|', 'show' => true, 'c' => [(object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]]];
        $stru2 = (object)['op' => '|', 'show' => true, 'c' => [(object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]]];
        $stru3 = (object)['op' => '|', 'show' => true, 'c' => [(object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 3]]];

        $tree1 = new \core_availability\tree($stru1);
        $tree2 = new \core_availability\tree($stru2);
        $tree3 = new \core_availability\tree($stru3);

        $this->assertFalse($tree1->check_available(false, $info, true, 0)->is_available());
        $result1 = $tree1->check_available(false, $info, true, $user->id);
        $result2 = $tree2->check_available(false, $info, true, $user->id);
        $result3 = $tree3->check_available(false, $info, true, $user->id);
        $this->assertFalse($result1->is_available());
        $this->assertFalse($result2->is_available());
        $this->assertFalse($result3->is_available());

        $result1 = $tree1->check_available(true, $info, true, $user->id);
        $result2 = $tree2->check_available(true, $info, true, $user->id);
        $result3 = $tree3->check_available(true, $info, true, $user->id);
        $this->assertTrue($result1->is_available());
        $this->assertFalse($result2->is_available());
        $this->assertTrue($result3->is_available());

        $course = $this->getDataGenerator()->create_course(['startdate' => time(), 'enddate' => time() + 7 * WEEKSECS]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $info = new \core_availability\mock_info($course, $user->id);
        $this->assertFalse($tree1->check_available(false, $info, true, 0)->is_available());
        $result1 = $tree1->check_available(false, $info, true, $user->id);
        $result2 = $tree2->check_available(false, $info, true, $user->id);
        $result3 = $tree3->check_available(false, $info, true, $user->id);
        $this->assertFalse($result1->is_available());
        $this->assertFalse($result2->is_available());
        $this->assertFalse($result3->is_available());

    }

    /**
     * Tests the constructor including error conditions.
     */
    public function test_constructor() {
        $structure = (object)['type' => 'relativedate'];
        $relativedatec = new condition($structure);
        $structure->n = 1;
        $relativedatec = new condition($structure);

        $structure->d = 1;
        $relativedatec = new condition($structure);

        $structure->d = '2';
        $relativedatec = new condition($structure);

        $structure->n = 'a';
        $relativedatec = new condition($structure);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)['n' => 1, 'd' => 2, 's' => 1];
        $cond = new condition($structure);
        $structure->type = 'relativedate';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     */
    public function test_get_description() {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['startdate' => time(), 'enddate' => time() + 7 * WEEKSECS]);
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $info = new \core_availability\mock_info($course, $user->id);
        $this->setUser($user);
        $relativedate = new condition((object)['type' => 'relativedate', 'n' => 1, 'd' => 1, 's' => 1]);
        $information = $relativedate->get_description(true, false, $info);
        $this->assertContains('1 days after course start date', $information);
        $information = $relativedate->get_description(true, true, $info);
        $this->assertContains('Not 1 days after course start date', $information);
        $information = $relativedate->get_standalone_description(false, false, $info);
        $this->assertNotContains('1 days after course start date', $information);
        $information = $relativedate->get_standalone_description(false, true, $info);
        $this->assertNotContains('Not 1 days after course start date', $information);

        $relativedate = new condition((object)['type' => 'relativedate', 'n' => 2, 'd' => 2, 's' => 2]);
        $information = $relativedate->get_description(true, false, $info);
        $this->assertContains('2 weeks before course end date', $information);
        $information = $relativedate->get_description(true, true, $info);
        $this->assertContains('Not 2 weeks before course end date', $information);
        $information = $relativedate->get_standalone_description(false, false, $info);
        $this->assertNotContains('2 weeks before course end date', $information);
        $information = $relativedate->get_standalone_description(false, true, $info);
        $this->assertNotContains('Not 2 weeks before course end date', $information);

        $relativedate = new condition((object)['type' => 'relativedate', 'n' => 3, 'd' => 3, 's' => 3]);
        $information = $relativedate->get_description(true, false, $info);
        $this->assertContains('3 months after', $information);
        $information = $relativedate->get_description(true, true, $info);
        $this->assertContains('Not 3 months after', $information);
        $information = $relativedate->get_standalone_description(false, false, $info);
        $this->assertNotContains('3 months after', $information);
        $information = $relativedate->get_standalone_description(false, true, $info);
        $this->assertNotContains('Not 3 months after', $information);

    }

    /**
     * Tests a course with no enddate.
     */
    public function test_noenddate() {
        global $CFG, $PAGE;
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enableavailability = true;
        $generator = $this->getDataGenerator();
        $course1 = $generator->create_course();
        $course2 = $generator->create_course(['enddate' => time() + 7 * WEEKSECS]);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course1->id);
        $generator->enrol_user($user->id, $course2->id);
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
        $this->assertEquals('7 weeks before course end date (No course enddate)', $information);
        $this->assertEquals('{relativedate: 7 weeks  before course end date}', "$cond");
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(false, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));

        $PAGE->set_url('/course/modedit.php', ['update' => $page2->cmid]);
        \core_availability\frontend::include_all_javascript($course2, $cm2);
        $info = new \core_availability\info_module($cm2);
        $information = $cond->get_description(true, false, $info);
        $this->assertContains('7 weeks before course end date', $information);
        $this->assertNotContains('(No course enddate)', $information);
        $this->assertEquals('{relativedate: 7 weeks  before course end date}', "$cond");
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertTrue($cond->is_available(false, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, false, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
    }

    /**
     * Test privacy.
     */
    public function test_privacy() {
        $privacy = new availability_relativedate\privacy\provider();
        $this->assertEquals($privacy->get_reason(), 'privacy:metadata');
    }
}