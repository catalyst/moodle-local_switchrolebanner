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
 * Data provider tests.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_switchrolebanner\privacy;

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use local_switchrolebanner\helper;

/**
 * Data provider testcase class.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_switchrolebanner\privacy\provider
 */
class provider_test extends provider_testcase {

    /**
     * Setup function.
     */
    public function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $this->c1 = $dg->create_course();
        $this->c2 = $dg->create_course();
        $this->c3 = $dg->create_course();
        $this->u1 = $dg->create_user();
        $this->u2 = $dg->create_user();
        $this->c1ctx = \context_course::instance($this->c1->id);
        $this->c2ctx = \context_course::instance($this->c2->id);
        $this->c3ctx = \context_course::instance($this->c3->id);
        $this->studentrole = $DB->get_record('role', ['shortname' => 'student']);
    }

    /**
     * Test that contexts are returned for a given user.
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        global $DB, $PAGE;

        $this->setUser($this->u1);
        $PAGE->set_course($this->c1);
        helper::set_user_last_role($this->studentrole->id);
        $PAGE->set_course($this->c2);
        helper::set_user_last_role($this->studentrole->id);
        $this->setUser($this->u2);
        helper::set_user_last_role($this->studentrole->id);

        $contextids = provider::get_contexts_for_userid($this->u1->id)->get_contextids();
        $this->assertCount(2, $contextids);
        $this->assertTrue(in_array($this->c1ctx->id, $contextids));
        $this->assertTrue(in_array($this->c2ctx->id, $contextids));

        $contextids = provider::get_contexts_for_userid($this->u2->id)->get_contextids();
        $this->assertCount(1, $contextids);
        $this->assertTrue(in_array($this->c2ctx->id, $contextids));
    }

    /**
     * Test that user IDs are returned for a given context.
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context() {
        global $DB, $PAGE;

        $u3 = $this->getDataGenerator()->create_user();

        $PAGE->set_course($this->c1);

        $this->setUser($this->u1);
        helper::set_user_last_role($this->studentrole->id);
        $this->setUser($this->u2);
        helper::set_user_last_role($this->studentrole->id);
        $this->setUser($u3);
        helper::set_user_last_role($this->studentrole->id);

        $userlist = new \core_privacy\local\request\userlist($this->c1ctx, 'local_switchrolebanner');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());
    }

    /**
     * Test that data is deleted for a given user.
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user() {
        global $DB, $PAGE;

        $usercourses = [
            $this->u1->id => [$this->c1, $this->c2, $this->c3],
            $this->u2->id => [$this->c2]
        ];
        $this->setPrefs($usercourses);
        $this->assertPrefExists($usercourses);

        provider::delete_data_for_user(new approved_contextlist($this->u1, 'local_switchrolebanner',
                [$this->c1ctx->id, $this->c2ctx->id]));
        $usercourses = [$this->u1->id => [$this->c1, $this->c2]];
        $this->assertPrefNotExists($usercourses);
        $usercourses = [
            $this->u1->id => [$this->c3],
            $this->u2->id => [$this->c2],
        ];
        $this->assertPrefExists($usercourses);
    }

    /**
     * Test that data is deleted for a given context.
     * @covers ::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB, $PAGE;

        $dg = $this->getDataGenerator();
        $c4 = $dg->create_course();
        $c4ctx = \context_course::instance($c4->id);

        $usercourses = [
            $this->u1->id => [$this->c1, $this->c2, $this->c3],
            $this->u2->id => [$this->c1, $this->c2]
        ];
        $this->setPrefs($usercourses);
        $this->assertPrefExists($usercourses);

        // Nothing happens.
        provider::delete_data_for_all_users_in_context($c4ctx);
        $this->assertPrefExists($usercourses);

        // Delete for course 1.
        provider::delete_data_for_all_users_in_context($this->c1ctx);
        unset($usercourses[$this->u1->id][0]);
        unset($usercourses[$this->u2->id][0]);
        $this->assertPrefExists($usercourses);
        $usercoursesdeleted = [
            $this->u1->id => [$this->c1],
            $this->u2->id => [$this->c1],
        ];
        $this->assertPrefNotExists($usercoursesdeleted);

        // Delete for course 2.
        provider::delete_data_for_all_users_in_context($this->c2ctx);
        $this->assertPrefExists([$this->u1->id => [$this->c3]]);
        $usercoursesdeleted = [
            $this->u1->id => [$this->c1, $this->c2],
            $this->u2->id => [$this->c1, $this->c2],
        ];
        $this->assertPrefNotExists($usercoursesdeleted);
    }

    /**
     * Test the deletion of data related to a context and a list of users.
     * @covers ::delete_data_for_users
     */
    public function test_delete_data_for_users() {
        global $DB, $PAGE;

        $u3 = $this->getDataGenerator()->create_user();

        $usercourses = [
            $this->u1->id => [$this->c1],
            $this->u2->id => [$this->c1],
            $u3->id => [$this->c1],
        ];
        $this->setPrefs($usercourses);
        $this->assertPrefExists($usercourses);

        $userlist = new \core_privacy\local\request\userlist($this->c1ctx, 'local_switchrolebanner');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());

        // Delete preferences for user 1 and 3 for course.
        $userlist = new \core_privacy\local\request\approved_userlist($this->c1ctx, 'local_switchrolebanner',
                [$this->u1->id, $u3->id]);
        provider::delete_data_for_users($userlist);

        // Only user 2's preference is left.
        unset($usercourses[$this->u2->id]);
        $this->assertPrefNotExists($usercourses);
        $this->assertPrefExists([$this->u2->id => [$this->c1]]);
    }

    /**
     * Test data is exported for a given user.
     * @covers ::export_data_for_user
     */
    public function test_export_data_for_user() {
        global $DB, $PAGE;

        $usercourses = [
            $this->u1->id => [$this->c1, $this->c2],
            $this->u2->id => [$this->c1, $this->c2, $this->c3],
        ];
        $this->setPrefs($usercourses);

        // Export data.
        provider::export_user_data(new approved_contextlist($this->u1, 'local_switchrolebanner',
            [$this->c1ctx->id, $this->c2ctx->id, $this->c3ctx->id]));
        $prefs = writer::with_context($this->c3ctx)->get_user_context_preferences('local_switchrolebanner');
        $this->assertEmpty((array) $prefs);

        $prefs = writer::with_context($this->c1ctx)->get_user_context_preferences('local_switchrolebanner');
        $key = helper::LAST_COURSE_ROLE . $this->c1->id;
        $this->assertEquals($this->studentrole->id, $prefs->$key->value);

        $prefs = writer::with_context($this->c2ctx)->get_user_context_preferences('local_switchrolebanner');
        $key = helper::LAST_COURSE_ROLE . $this->c2->id;
        $this->assertEquals($this->studentrole->id, $prefs->$key->value);
    }

    /**
     * Sets the preferences for an array of users and courses.
     * @param array $usercourses an array of userid key and courses that the preference needs to be set for
     */
    private function setPrefs($usercourses) {
        global $PAGE;

        foreach($usercourses as $userid => $courses) {
            $this->setUser($userid);
            foreach ($courses as $course) {
                $PAGE->set_course($course);
                helper::set_user_last_role($this->studentrole->id);
            }
        }
    }

    /**
     * Asserts the prefence exists for an array of users and courses.
     * @param array $usercourses an array of userid key and courses that the preference should exist for
     * @param bool $not if we are testing for not exists
     */
    private function assertPrefExists($usercourses, $not = false) {
        global $DB;

        foreach ($usercourses as $userid => $courses) {
            foreach ($courses as $course) {
                $params = ['userid' => $userid, 'name' => helper::LAST_COURSE_ROLE . $course->id];
                if ($not) {
                    $this->assertFalse($DB->record_exists('user_preferences', $params));
                } else {
                    $this->assertTrue($DB->record_exists('user_preferences', $params));
                }
            }
        }
    }

    /**
     * Asserts the prefence does not exists for an array of users and courses.
     * @param array $usercourses an array of userid key and courses that the preference should exist for
     */
    private function assertPrefNotExists($usercourses) {
        $this->assertPrefExists($usercourses, true);
    }
}
