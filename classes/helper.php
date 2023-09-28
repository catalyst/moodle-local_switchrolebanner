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
 * Helper class.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_switchrolebanner;

use context_course;
use context_coursecat;
use html_writer;

defined('MOODLE_INTERNAL') || die;

/**
 * Class helper.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * Return if the banner should be shown or not.
     *
     * @return bool if the banner should be shown
     */
    public static function should_show_banner() : bool {
        global $PAGE;

        if ($PAGE->course->id == SITEID) {
            return false;
        }

        if (!self::has_admin_role()) {
            return false;
        }

        if (empty($courseroles = self::get_user_course_role())) {
            return false;
        }

        return true;
    }

    /**
     * Check if the user has a role to view the current course at a context higher than
     * the course context.
     *
     * @return bool if the user has an admin role
     */
    public static function has_admin_role() : bool {
        global $USER, $COURSE;

        if (isguestuser($USER)) {
            return false;
        }

        $context = context_coursecat::instance($COURSE->category, MUST_EXIST);

        return has_capability('moodle/course:view', $context, $USER);
    }

    /**
     * Get the user's roles in the course.
     *
     * @return array an array of roles
     */
    public static function get_user_course_role() : array {
        global $USER, $COURSE;

        $userid = $USER->id;
        $context = context_course::instance($COURSE->id, MUST_EXIST);
        $usersroles = get_users_roles($context, [$userid], false);

        return $usersroles[$userid];
    }

    /**
     * Builds and returns the banner HTML.
     *
     * @return string banner HTML
     */
    public static function get_banner_html() : string {
        global $OUTPUT;

        return $OUTPUT->render_from_template('local_switchrolebanner/banner', []);
    }
}
