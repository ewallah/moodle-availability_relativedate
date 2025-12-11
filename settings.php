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
 * Relativedate condition settings.
 *
 * @package   availability_relativedate
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $component = 'availability_relativedate';
    $maxnumber = during_initial_install() ? 100 : get_config($component, 'maxnumber') + 1;

    $settings->add(
        new admin_setting_heading(
            $component . '_settings',
            '',
            get_string('settings', $component)
        )
    );

    $settings->add(
        new admin_setting_configtext(
            name: "{$component}/maxnumber",
            visiblename: get_string(
                identifier: 'maxnumber',
                component: $component
            ),
            description: get_string(
                identifier: 'maxnumber_help',
                component: $component
            ),
            defaultsetting: 59,
            paramtype: PARAM_INT,
            size: 6
        )
    );


    $settings->add(
        new admin_setting_heading(
            $component . '_defaults',
            '',
            get_string('defaults', $component)
        )
    );
    $optionsnumber = [];
    for ($i = 1; $i < $maxnumber; $i++) {
        $optionsnumber[$i] = $i;
    }

    $settings->add(
        new admin_setting_configselect(
            name: "{$component}/defaultnumber",
            visiblename: get_string(
                identifier: 'defaultnumber',
                component: $component
            ),
            description: '',
            defaultsetting: 1,
            choices: $optionsnumber
        )
    );

    $settings->add(
        new admin_setting_configselect(
            name: "{$component}/defaultdwm",
            visiblename: get_string(
                identifier: 'defaultdwm',
                component: $component
            ),
            description: null,
            defaultsetting: 2,
            choices: availability_relativedate\condition::options_dwm(2)
        )
    );

    $optionsstart = [];
    for ($i = 1; $i < 8; $i++) {
        $optionsstart[$i] = availability_relativedate\condition::options_start($i);
    }

    $settings->add(
        new admin_setting_configselect(
            name: "{$component}/defaultstart",
            visiblename: get_string(
                identifier: 'defaultstart',
                component: $component
            ),
            description: null,
            defaultsetting: 1,
            choices: $optionsstart
        )
    );
}
