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
 * Privacy Subsystem implementation for local_switchrolebanner.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_switchrolebanner\privacy;

use context;
use core_privacy\local\request\writer;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use local_switchrolebanner\helper;

/**
 * Privacy provider for local_switchrolebanner.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\subsystem\provider,
    \core_privacy\local\request\user_preference_provider,
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $items The initialised item collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $items) : collection {
        // There is one user preference.
        $items->add_user_preference(helper::LAST_COURSE_ROLE . 'ID', 'privacy:metadata:preference:lastcourserole');
        return $items;
    }

    /**
     * Export all user preferences for the plugin.
     *
     * @param int $userid The userid of the user whose data is to be exported.
     */
    public static function export_user_preferences(int $userid) {
        // Our preferences aren't site-wide so they are exported in export_user_data.
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist $contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        global $DB;

        $contextlist = new contextlist();

        // Fetch the course IDs.
        $likeprefkey = $DB->sql_like('name', ':prefkey', false, false);
        $sql = "userid = :userid AND $likeprefkey";
        $params = [
            'userid' => $userid,
            'prefkey' => helper::LAST_COURSE_ROLE . '%',
        ];
        $prefs = $DB->get_fieldset_select('user_preferences', 'name', $sql, $params);

        $instanceids = array_unique(array_map(function($prefname) {
            if (preg_match('/^' . helper::LAST_COURSE_ROLE . '(\d+)$/', $prefname, $matches)) {
                return $matches[1];
            }
            return 0;
        }, $prefs));

        // Find the context of the instances.
        if (!empty($instanceids)) {
            list($insql, $inparams) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
            $sql = "
                SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.instanceid $insql
                   AND ctx.contextlevel = :courselevel";
            $params = array_merge($inparams, ['courselevel' => CONTEXT_COURSE]);
            $contextlist->add_from_sql($sql, $params);
        }

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        $params = ['prefkey' => helper::LAST_COURSE_ROLE . $context->instanceid];

        $sql = "SELECT userid
                  FROM {user_preferences}
                 WHERE name = :prefkey";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;

        // Extract the course IDs.
        $instanceids = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $carry[] = $context->instanceid;
            }
            return $carry;
        }, []);
        if (empty($instanceids)) {
            return;
        }

        // Query the courses and their preferences.
        list($insql, $inparams) = $DB->get_in_or_equal($instanceids, SQL_PARAMS_NAMED);
        $prefkey = $DB->sql_concat("'" . helper::LAST_COURSE_ROLE . "'", 'c.id');
        $sql = "
            SELECT c.id AS courseid,
                   c.fullname AS coursename,
                   p.name AS prefkey,
                   p.value AS roleid
              FROM {course} c
              JOIN {user_preferences} p
                ON p.userid = :userid
             WHERE c.id $insql
               AND p.name = $prefkey";
        $params = array_merge($inparams, [
            'userid' => $userid,
        ]);

        // Export the preferences.
        $recordset = $DB->get_recordset_sql($sql, $params);
        foreach ($recordset as $record) {
            $context = \context_course::instance($record->courseid);
            $rolenames = role_get_names($context);
            writer::with_context($context)->export_user_preference(
                'local_switchrolebanner',
                $record->prefkey,
                $record->roleid,
                get_string('privacy:request:preference:lastcourserole', 'local_switchrolebanner', (object) [
                    'rolename' => $rolenames[$record->roleid]->localname,
                    'coursename' => $record->coursename,
                ])
            );
        }
        $recordset->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        // Delete the user preferences.
        $instanceid = $context->instanceid;
        $DB->delete_records_list('user_preferences', 'name', [helper::LAST_COURSE_ROLE . $instanceid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        $prefkeys = array_reduce($contextlist->get_contexts(), function($carry, $context) {
            if ($context->contextlevel == CONTEXT_COURSE) {
                $carry[] = helper::LAST_COURSE_ROLE . $context->instanceid;
            }
            return $carry;
        }, []);

        if (empty($prefkeys)) {
            return;
        }

        list($insql, $inparams) = $DB->get_in_or_equal($prefkeys, SQL_PARAMS_NAMED);
        $sql = "userid = :userid AND name $insql";
        $params = array_merge($inparams, ['userid' => $userid]);
        $DB->delete_records_select('user_preferences', $sql, $params);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete
     * information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['prefkey'] = helper::LAST_COURSE_ROLE . $context->instanceid;

        $DB->delete_records_select('user_preferences', "name = :prefkey AND userid $insql", $params);
    }
}
