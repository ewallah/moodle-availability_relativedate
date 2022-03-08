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
 * Step definitions to add enrolment.
 *
 * @package   availability_relativedate
 * @copyright 2019 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.
// For that reason, we can't even rely on $CFG->admin being available here.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Step definitions to add enrolment.
 *
 * @package   availability_relativedate
 * @copyright 2019 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_availability_relativedate extends behat_base {


    /**
     * See a relative date
     * @Then /^I should see relativedate "([^"]*)"$/
     * @param string $date
     */
    public function i_should_see_relativedate($date) {
        global $USER;
        $times = array_filter(explode('##', $date));
        $time = reset($times);
        $stime = userdate($time, get_string('strftimedate', 'langconfig'), $USER->timezone);
        $this->execute("behat_general::assert_element_contains_text", [$stime, '.course-content', 'css_element']);
    }

    /**
     * Add a self enrolment method starting
     * @Given /^selfenrolment exists in course "(?P<course>[^"]*)" starting "(?P<date>[^"]*)"$/
     * @param string $course
     * @param string $date
     */
    public function selfenrolment_exists_in_course_starting($course, $date) {
        $this->config_self_enrolment($course, $date, '');
    }

    /**
     * Add a self enrolment method ending
     * @Given /^selfenrolment exists in course "(?P<course>[^"]*)" ending "(?P<date>[^"]*)"$/
     * @param string $course
     * @param string $date
     */
    public function selfenrolment_exists_in_course_ending($course, $date) {
        $this->config_self_enrolment($course, '', $date);
    }

    /**
     * Configure self enrolment
     * @param string $course
     * @param string $start
     * @param string $end
     */
    private function config_self_enrolment($course, $start, $end) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/enrol/self/lib.php');
        $courseid = $this->get_course_id($course);
        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'self'], '*', MUST_EXIST);
        $instance->customint6 = 1;
        $instance->enrolstartdate = $this->get_transformed_timestamp($start);
        $instance->enrolenddate = $this->get_transformed_timestamp($end);
        $DB->update_record('enrol', $instance);
        $selfplugin->update_status($instance, ENROL_INSTANCE_ENABLED);
    }

    /**
     * Return timestamp for the time passed.
     *
     * @param string $time time to convert
     * @return string
     */
    protected function get_transformed_timestamp($time) {
        if ($time === '') {
             return 0;
        }
        $timepassed = array_filter(explode('##', $time));
        $first = reset($timepassed);
        $sfirst = strtotime($first);
        return ($sfirst == '') ? $first : $sfirst;
    }
}
