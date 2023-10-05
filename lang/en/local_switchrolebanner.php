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
 * Lang strings.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Switch role banner';

$string['canselfenrol'] = 'You are currently viewing this course with your site or course category role but can enrol yourself in this course.';
$string['viewingasadmin'] = 'You are currently viewing this course with your site or course category role.';
$string['viewingasrole'] = 'You are currently viewing this course with your <b>{$a}</b> course role.';

$string['privacy:metadata:preference:lastcourserole'] = 'Records the last role the user switched to in a course';
$string['privacy:request:preference:lastcourserole'] = 'You last switched to the "{$a->rolename}" role for "{$a->coursename}"';
