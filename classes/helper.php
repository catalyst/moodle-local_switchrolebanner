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
use local_switchrolebanner\output\banner;

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
     * @var array User's course roles
     */
    private static $courseroles = null;

    /**
     * @var array User's switchable course roles
     */
    private static $switchablecourseroles = null;

    /**
     * @var int User's switched course role
     */
    private static $switchedcourserole = null;

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

        if (empty(self::get_user_course_roles())) {
            return false;
        }

        $switchablecourseroles = self::get_user_switchable_course_roles();
        $switchedcourserole = self::get_user_switched_course_role();
        if (empty($switchablecourseroles) && empty($switchedcourserole)) {
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
     * Get the user's roles in the course that are switchable.
     *
     * @return array an array of roles
     */
    public static function get_user_switchable_course_roles() : array {
        global $USER, $COURSE;

        if (!is_null(self::$switchablecourseroles)) {
            return self::$switchablecourseroles;
        }

        $userid = $USER->id;
        $context = context_course::instance($COURSE->id, MUST_EXIST);

        $courseroles = self::get_user_course_roles();
        if (empty($courseroles)) {
            self::$switchablecourseroles = [];
            return self::$switchablecourseroles;
        };

        $switchableroles = get_switchable_roles($context);
        if (empty($switchableroles)) {
            self::$switchablecourseroles = [];
            return self::$switchablecourseroles;
        };

        foreach ($switchableroles as $rid => $switchablerole) {
            if (!array_key_exists($rid, $courseroles)) {
                unset($switchableroles[$rid]);
            }
        }

        self::$switchablecourseroles = $switchableroles;
        return self::$switchablecourseroles;
    }

    /**
     * Get the user's roles in the course.
     *
     * @return array an array of roles
     */
    public static function get_user_course_roles() : array {
        global $USER, $COURSE;

        if (!is_null(self::$courseroles)) {
            return self::$courseroles;
        }

        $context = context_course::instance($COURSE->id, MUST_EXIST);
        $ras = get_user_roles($context, $USER->id, false);

        $roles = [];
        foreach ($ras as $ra) {
            $roles[$ra->roleid] = $ra->shortname;
        }

        self::$courseroles = $roles;
        return self::$courseroles;
    }

    /**
     * Get the user's current switched role if is one of their course roles.
     *
     * @return int the id of the switched role or 0 if role has not been switched
     */
    public static function get_user_switched_course_role() : int {
        global $USER, $COURSE;

        if (!is_null(self::$switchedcourserole)) {
            return self::$switchedcourserole;
        }

        $role = 0;

        $context = context_course::instance($COURSE->id, MUST_EXIST);
        if (!empty($USER->access['rsw'][$context->path])) {
            $rid = $USER->access['rsw'][$context->path];
            $courseroles = self::get_user_course_roles();
            if (array_key_exists($rid, $courseroles)) {
                $role = $rid;
            }
        }

        self::$switchedcourserole = $role;
        return self::$switchedcourserole;
    }

    /**
     * Builds and returns the banner HTML.
     *
     * @return string banner HTML
     */
    public static function get_banner_html() : string {
        global $PAGE;

        $renderable = new banner();
        $renderer = $PAGE->get_renderer('local_switchrolebanner');

        return $renderer->render($renderable);
    }
}
