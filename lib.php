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
 * Lib functions.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_switchrolebanner\helper;

/**
 * after_require_login callback to save or switch to last role if required.
 *
 * @return void
 */
function local_switchrolebanner_after_require_login() : void {
    helper::handle_role_switch();
}

/**
 * before_footer callback to add the banner to the page if conditions are correct.
 * 
 * @return string HTML the banner if conditions are correct or an empty string.
 */
function local_switchrolebanner_before_footer() : string {
    if (helper::should_show_banner()) {
        return helper::get_banner_html();
    }

    return '';
}
