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

use advanced_testcase;
use availability_relativedate\condition;
use core_availability\{tree, info_module, info_section};
use core\di;
use core\clock;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the relativedate condition.
 *
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(condition::class)]
final class backup_test extends advanced_testcase {
    /** @var stdClass course. */
    private $course;

    /**
     * Create course and page.
     */
    public function setUp(): void {
        global $CFG;
        parent::setUp();
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
        $this->resetAfterTest();
        $this->preventResetByRollback();
        $this->setAdminUser();
        $CFG->enablecompletion = true;
        $CFG->enableavailability = true;

        $dg = $this->getDataGenerator();
        $now = di::get(clock::class)->time();
        $this->course = $dg->create_course(['startdate' => $now, 'enddate' => $now + 7 * WEEKSECS, 'enablecompletion' => 1]);
    }

    /**
     * Backup course check.
     */
    public function test_backup_course(): void {
        global $CFG, $DB;
        $CFG->backup_database_logger_level = \backup::LOG_DEBUG;
        $CFG->backup_error_log_logger_level = \backup::LOG_DEBUG;
        $CFG->backup_file_logger_level = \backup::LOG_DEBUG;
        $CFG->backup_file_logger_level_extra = \backup::LOG_DEBUG;

        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_page');
        $page0 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page1 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page2 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page3 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page4 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $str = '{"op":"|","show":true,"c":[{"type":"relativedate","n":4,"d":4,"s":7,"m":' . $page0->cmid . '}]}';
        $DB->set_field('course_modules', 'availability', $str, ['id' => $page1->cmid]);
        $str = '{"op":"|","show":true,"c":[{"type":"relativedate","n":3,"d":4,"s":7,"m":' . $page2->cmid . '}]}';
        $DB->set_field('course_modules', 'availability', $str, ['id' => $page3->cmid]);
        $str = '{"op":"|","c":[{"type":"relativedate","n":1,"d":1,"s":7,"m":999999}], "show":true}';
        $DB->set_field('course_modules', 'availability', $str, ['id' => $page4->cmid]);
        rebuild_course_cache($this->course->id, true);
        $this->assertCount(5, get_fast_modinfo($this->course)->get_instances_of('page'));

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        $logger = new \core_backup_html_logger(\backup::LOG_DEBUG);
        $newcourse = $dg->create_course(['enablecompletion' => 1]);
        $rc = new \restore_controller(
            'test-restore-course-event',
            $newcourse->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2,
            \backup::TARGET_NEW_COURSE
        );
        $rc->add_logger($logger);
        $rc->execute_precheck();
        $rc->execute_plan();

        // TODO:  We should see a warning.
        $this->assertNotEquals('', $logger->get_html());
        $rc->destroy();

        $modinfo = get_fast_modinfo($newcourse);
        $pages = $modinfo->get_instances_of('page');
        $this->assertCount(5, $pages);
        $arr = [];
        foreach ($pages as $page) {
            if ($page->availability) {
                $arr[] = $page->availability;
            }
        }

        $this->assertStringContainsString('[{"type":"relativedate","n":4,"d":4,"s":7,"m"', $arr[0]);
        $this->assertStringNotContainsString($page0->cmid, $arr[0]);
        $this->assertStringContainsString('[{"type":"relativedate","n":3,"d":4,"s":7,"m"', $arr[1]);
        $this->assertStringNotContainsString($page2->cmid, $arr[1]);
        $this->assertStringContainsString('[{"type":"relativedate","n":1,"d":1,"s":7,"m":0}]', $arr[2]);
    }

    /*
     * Backup same course.
     */
    public function test_backup_same_course(): void {
        global $CFG, $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_page');
        $page0 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page1 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $str = '{"op":"|","show":true,"c":[{"type":"relativedate","n":2,"d":5,"s":7,"m":' . $page0->cmid . '}]}';
        $DB->set_field('course_modules', 'availability', $str, ['id' => $page1->cmid]);

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $this->course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $fp = get_file_packer('application/vnd.moodle.backup');
        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $file->extract_to_pathname($fp, $filepath);
        $bc->destroy();

        $rc = new \restore_controller(
            'test-restore-course-event',
            $this->course->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2,
            \backup::TARGET_CURRENT_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        $modinfo = get_fast_modinfo($this->course);
        $pages = $modinfo->get_instances_of('page');
        $this->assertCount(4, $pages);
        $arr = [];
        foreach ($pages as $page) {
            if (!is_null($page->availability)) {
                $arr[] = $page->availability;
            }
        }

        $this->assertStringContainsString('[{"type":"relativedate","n":2,"d":5,"s":7,"m":' . $page0->cmid, $arr[0]);
        $this->assertStringContainsString($page0->cmid, $arr[0]);
        $this->assertStringContainsString('[{"type":"relativedate","n":2,"d":5,"s":7,"m"', $arr[1]);
        $this->assertStringNotContainsString($page0->cmid, $arr[1]);
    }

    /*
     * Backup module.
     */
    public function test_backup_module(): void {
        global $CFG, $DB;
        $dg = $this->getDataGenerator();
        $pg = $dg->get_plugin_generator('mod_page');
        $page0 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $page1 = $pg->create_instance(['course' => $this->course, 'completion' => COMPLETION_TRACKING_MANUAL]);
        $str = '{"op":"|","show":true,"c":[{"type":"relativedate","n":66,"d":5,"s":7,"m":' . $page0->cmid . '}]}';
        $DB->set_field('course_modules', 'availability', $str, ['id' => $page1->cmid]);
        $modinfo = get_fast_modinfo($this->course);
        $cms = $modinfo->get_instances();
        $cm = $cms['page'][$page1->id];
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cm->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2
        );
        $bc->execute_plan();

        $filepath = $CFG->dataroot . '/temp/backup/test-restore-course-event';
        $fp = get_file_packer('application/vnd.moodle.backup');
        $results = $bc->get_results();
        $file = $results['backup_destination'];
        $file->extract_to_pathname($fp, $filepath);

        $bc->destroy();

        $rc = new \restore_controller(
            'test-restore-course-event',
            $this->course->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            2,
            \backup::TARGET_CURRENT_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();

        $modinfo = get_fast_modinfo($this->course);
        $pages = $modinfo->get_instances_of('page');
        $this->assertCount(3, $pages);
        $arr = [];
        foreach ($pages as $page) {
            if (!is_null($page->availability)) {
                $arr[] = $page->availability;
            }
        }

        $this->assertStringContainsString('[{"type":"relativedate","n":66,"d":5,"s":7,"m":' . $page0->cmid, $arr[0]);
        $this->assertStringContainsString($page0->cmid, $arr[0]);
        $this->assertStringContainsString('[{"type":"relativedate","n":66,"d":5,"s":7,"m"', $arr[1]);
        $this->assertStringContainsString($page0->cmid, $arr[1]);
    }
}
