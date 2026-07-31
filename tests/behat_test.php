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
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace availability_relativedate;

use availability_relativedate\condition;
use core_availability\info_module;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the relativedate condition.
 *
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\behat_availability_relativedate::class)]
final class behat_test extends \advanced_testcase {
    /**
     * Enable completion and availability.
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;
        set_config('enableavailability', true);
    }

    /**
     * Test behat funcs
     */
    public function test_behat(): void {
        global $CFG;
        require_once($CFG->dirroot . '/availability/condition/relativedate/tests/behat/behat_availability_relativedate.php');
        $dg = $this->getDataGenerator();
        $course = $dg->create_course(['enablecompletion' => true]);
        $dg->get_plugin_generator('mod_page')->create_instance(['course' => $course, 'idnumber' => 'page1']);
        $dg->get_plugin_generator('mod_page')->create_instance(['course' => $course, 'idnumber' => 'page2']);
        $class = new \behat_availability_relativedate();
        $class->selfenrolment_exists_in_course_starting($course->fullname, '');
        $class->selfenrolment_exists_in_course_starting($course->fullname, '##-10 days noon##');
        $class->selfenrolment_exists_in_course_ending($course->fullname, '');
        $class->selfenrolment_exists_in_course_ending($course->fullname, '## today ##');
        $this->expectExceptionMessage('behat_context_helper');
        $class->i_make_activity_relative_date_depending_on('page1', 'page2');
        $class->i_should_see_relativedate('##-10 days noon##');
    }
}
