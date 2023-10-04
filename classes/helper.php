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
     * The user preference value to indicate the last role a user switched to.
     *
     * @var LAST_COURSE_ROLE
     */
    const LAST_COURSE_ROLE = 'local_switchrolebanner_last_course_role_';

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

        if (self::is_banner_hidden()) {
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

    /**
     * Sets the last course role as a user preference. This is to be used to return the user
     * to their last switched role on their next session.
     *
     * @param int $roleid the role id to store
     * @return void
     */
    public static function set_user_last_role(int $roleid) : void {
        global $COURSE;

        $key = self::LAST_COURSE_ROLE . $COURSE->id;
        if ($roleid == 0) {
            unset_user_preference($key);
        } else {
            set_user_preference($key, $roleid);
        }
    }

    /**
     * Gets the last course role from user preference. This is to be used to return the user
     * to their last switched role on a new session.
     *
     * @return int the last role id the user switched to for this course
     */
    public static function get_user_last_role() : int {
        global $COURSE;

        return get_user_preferences(self::LAST_COURSE_ROLE . $COURSE->id, 0);
    }

    /**
     * Handles all role switch processing, i.e. setting last switched role or switching to
     * role last switched to in a previous session.
     *
     * @return void
     */
    public static function handle_role_switch() : void {
        global $COURSE;

        if ($COURSE->id == SITEID) {
            return;
        }

        $switchrole = optional_param('switchrole', -1, PARAM_INT);
        $switchablecourseroles = self::get_user_switchable_course_roles();

        // If we are switching to a relevant role set it as the last role and then return.
        $isswitchablecourserole = array_key_exists($switchrole, $switchablecourseroles);
        if ($switchrole > -1 && ($isswitchablecourserole || $switchrole == 0)) {
            self::set_user_last_role($switchrole);
            return;
        }

        // If we have already switched roles or there is no last switched role just return.
        if (is_role_switched($COURSE->id) || !$roleid = self::get_user_last_role()) {
            return;
        }

        // Is the last switched role we saved still a switchable enrolled role?
        if (array_key_exists($roleid, $switchablecourseroles)) {
            $context = context_course::instance($COURSE->id, MUST_EXIST);
            role_switch($roleid, $context);
        } else {
            // For some reason this role doesn't meet our conditions, let's just reset the preference.
            self::set_user_last_role(0);
        }
    }

    /**
     * Returns if the user has hidden the banner for this session.
     *
     * @return bool if the banner has been hidden
     */
    public static function is_banner_hidden() : bool {
        global $SESSION, $COURSE;

        if (!isset($SESSION->local_switchrolebanner_hide)) {
            return false;
        }

        $hiddencourselist = json_decode($SESSION->local_switchrolebanner_hide);
        return in_array($COURSE->id, $hiddencourselist);
    }

    /**
     * Hides the banner for the remainder of the session.
     *
     * @param int $courseid the id of the course to hide the banner for
     * @return void
     */
    public static function hide_banner($courseid) : void {
        global $SESSION;

        if (isset($SESSION->local_switchrolebanner_hide)) {
            $hiddencourselist = json_decode($SESSION->local_switchrolebanner_hide);
        } else {
            $hiddencourselist = [];
        }

        $hiddencourselist[] = $courseid;
        $SESSION->local_switchrolebanner_hide = json_encode($hiddencourselist);
    }
}
