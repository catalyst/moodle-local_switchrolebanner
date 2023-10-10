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
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that contexts are returned for a given user.
     * @covers ::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid() {
        global $DB, $PAGE;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1ctx = \context_course::instance($c1->id);
        $c2ctx = \context_course::instance($c2->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $this->setUser($u1);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        helper::set_user_last_role($studentrole->id);

        $contextids = provider::get_contexts_for_userid($u1->id)->get_contextids();
        $this->assertCount(2, $contextids);
        $this->assertTrue(in_array($c1ctx->id, $contextids));
        $this->assertTrue(in_array($c2ctx->id, $contextids));

        $contextids = provider::get_contexts_for_userid($u2->id)->get_contextids();
        $this->assertCount(1, $contextids);
        $this->assertTrue(in_array($c2ctx->id, $contextids));
    }

    /**
     * Test that user IDs are returned for a given context.
     * @covers ::get_users_in_context
     */
    public function test_get_users_in_context() {
        global $DB, $PAGE;

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecotext = \context_course::instance($course->id);

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $PAGE->set_course($course);

        $this->setUser($u1);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u3);
        helper::set_user_last_role($studentrole->id);

        $userlist = new \core_privacy\local\request\userlist($coursecotext, 'local_switchrolebanner');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());
    }

    /**
     * Test that data is deleted for a given user.
     * @covers ::delete_data_for_user
     */
    public function test_delete_data_for_user() {
        global $DB, $PAGE;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c3 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1ctx = \context_course::instance($c1->id);
        $c2ctx = \context_course::instance($c2->id);
        $c3ctx = \context_course::instance($c3->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $this->setUser($u1);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c3);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);

        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));

        provider::delete_data_for_user(new approved_contextlist($u1, 'local_switchrolebanner',
                [$c1ctx->id, $c2ctx->id]));

        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
    }

    /**
     * Test that data is deleted for a given context.
     * @covers ::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context() {
        global $DB, $PAGE;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c2 = $dg->create_course();
        $c3 = $dg->create_course();
        $c4 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1ctx = \context_course::instance($c1->id);
        $c2ctx = \context_course::instance($c2->id);
        $c3ctx = \context_course::instance($c3->id);
        $c4ctx = \context_course::instance($c4->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $this->setUser($u1);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c3);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);

        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));

        // Nothing happens.
        provider::delete_data_for_all_users_in_context($c4ctx);
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));

        // Delete for course 1.
        provider::delete_data_for_all_users_in_context($c1ctx);
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));

        // Delete for course 2.
        provider::delete_data_for_all_users_in_context($c2ctx);
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $c3->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c1->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $c2->id]));
    }

    /**
     * Test the deletion of data related to a context and a list of users.
     * @covers ::delete_data_for_users
     */
    public function test_delete_data_for_users() {
        global $DB, $PAGE;

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $coursecotext = \context_course::instance($course->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $PAGE->set_course($course);
        $this->setUser($u1);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u3);
        helper::set_user_last_role($studentrole->id);

        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u3->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));

        $userlist = new \core_privacy\local\request\userlist($coursecotext, 'local_switchrolebanner');
        provider::get_users_in_context($userlist);
        $this->assertCount(3, $userlist->get_userids());

        // Delete preferences for user 1 and 3 for course.
        $userlist = new \core_privacy\local\request\approved_userlist($coursecotext, 'local_switchrolebanner',
                [$u1->id, $u3->id]);
        provider::delete_data_for_users($userlist);

        // Only user 2's preference is left.
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u1->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));
        $this->assertTrue($DB->record_exists('user_preferences',
            ['userid' => $u2->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));
        $this->assertFalse($DB->record_exists('user_preferences',
            ['userid' => $u3->id, 'name' => helper::LAST_COURSE_ROLE . $course->id]));
    }

    /**
     * Test data is exported for a given user.
     * @covers ::export_data_for_user
     */
    public function test_export_data_for_user() {
        global $DB, $PAGE;

        $dg = $this->getDataGenerator();
        $c1 = $dg->create_course();
        $c2 = $dg->create_course();
        $c3 = $dg->create_course();
        $u1 = $dg->create_user();
        $u2 = $dg->create_user();
        $c1ctx = \context_course::instance($c1->id);
        $c2ctx = \context_course::instance($c2->id);
        $c3ctx = \context_course::instance($c3->id);
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        $this->setUser($u1);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);
        $this->setUser($u2);
        $PAGE->set_course($c1);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c2);
        helper::set_user_last_role($studentrole->id);
        $PAGE->set_course($c3);
        helper::set_user_last_role($studentrole->id);

        // Export data.
        provider::export_user_data(new approved_contextlist($u1, 'local_switchrolebanner',
            [$c1ctx->id, $c2ctx->id, $c3ctx->id]));
        $prefs = writer::with_context($c3ctx)->get_user_context_preferences('local_switchrolebanner');
        $this->assertEmpty((array) $prefs);

        $prefs = writer::with_context($c1ctx)->get_user_context_preferences('local_switchrolebanner');
        $key = helper::LAST_COURSE_ROLE . $c1->id;
        $this->assertEquals($studentrole->id, $prefs->$key->value);

        $prefs = writer::with_context($c2ctx)->get_user_context_preferences('local_switchrolebanner');
        $key = helper::LAST_COURSE_ROLE . $c2->id;
        $this->assertEquals($studentrole->id, $prefs->$key->value);
    }
}
