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
 * Tests for helper class.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/externallib.php');

use local_switchrolebanner\helper;

/**
 * Class helper_test.
 */
class helper_test extends advanced_testcase {
    /**
     * Test course instance.
     *
     * @var object course
     */
    protected $course;

    /**
     * Test user instance.
     *
     * @var object user
     */
    protected $user;

    /**
     * Initial set up.
     */
    protected function setUp(): void {
        global $DB, $PAGE;

        parent::setUp();
        $this->resetAfterTest();

        $dg = $this->getDataGenerator();
        $this->course = $dg->create_course();
        $this->user = $dg->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);

        $dg->role_assign($managerrole->id, $this->user->id, context_system::instance()->id);
        $dg->enrol_user($this->user->id, $this->course->id, 'student');

        $PAGE->set_course($this->course);
        $PAGE->set_url('/course/view.php', ['id' => $this->course->id]);
        $this->setUser($this->user);
    }

    /**
     * Test that is_excluded_page returns correctly for given scenarios.
     */
    public function test_is_excluded_page() {
        global $PAGE;

        // We are on a course view page.
        $this->assertFalse(helper::is_excluded_page());
        $this->assertTrue(helper::should_show_banner());

        // Site pages should be excluded.
        $PAGE->set_course(get_site());
        $this->assertTrue(helper::is_excluded_page());
        $this->assertFalse(helper::should_show_banner());

        // Reset course and double check it's still valid.
        $PAGE->set_course($this->course);
        $this->assertFalse(helper::is_excluded_page());
        $this->assertTrue(helper::should_show_banner());

        // We are on the enrol page which should be an excluded page.
        $PAGE->set_url('/enrol/index.php', ['id' => $this->course->id]);
        $this->assertTrue(helper::is_excluded_page());
        $this->assertFalse(helper::should_show_banner());

        // Reset page url and double check it's still valid.
        $PAGE->set_url('/course/view.php', ['id' => $this->course->id]);
        $this->assertFalse(helper::is_excluded_page());
        $this->assertTrue(helper::should_show_banner());

        // Popup page layout should be excluded.
        $PAGE->set_pagelayout('popup');
        $this->assertTrue(helper::is_excluded_page());
        $this->assertFalse(helper::should_show_banner());

        // Embedded page layout should be excluded.
        $PAGE->set_pagelayout('embedded');
        $this->assertTrue(helper::is_excluded_page());
        $this->assertFalse(helper::should_show_banner());
    }

    /**
     * Test that has_admin_role returns correctly for given scenarios.
     */
    public function test_has_admin_role() {
        global $DB;

        // User has sitewide role.
        $this->assertTrue(helper::has_admin_role());
        $this->assertTrue(helper::should_show_banner());

        // User does not have sitewide role.
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        role_unassign($managerrole->id, $this->user->id, context_system::instance()->id);
        $this->assertFalse(helper::has_admin_role());
        $this->assertFalse(helper::should_show_banner());
    }

    /**
     * Test that get_user_switchable_course_roles returns correct roles.
     */
    public function test_get_user_switchable_course_roles() {
        global $DB;

        $dg = $this->getDataGenerator();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);

        // Only student role is expected.
        $roles = helper::get_user_switchable_course_roles();
        $expected = [$studentrole->id => 'Student'];
        $this->assertEquals($expected, $roles);

        // Test with 2 roles, student and teacher role is expected.
        $dg->enrol_user($this->user->id, $this->course->id, 'teacher');
        $roles = helper::get_user_switchable_course_roles();
        $expected = [$studentrole->id => 'Student', $teacherrole->id => 'Non-editing teacher'];
        $this->assertEquals($expected, $roles);

        // If we switch role to student we should no longer be able to switch to other roles.
        role_switch($studentrole->id, context_course::instance($this->course->id));
        $roles = helper::get_user_switchable_course_roles();
        $expected = [];
        $this->assertEquals($expected, $roles);
    }

    /**
     * Test that get_user_course_roles returns correct roles.
     */
    public function test_get_user_course_roles() {
        global $DB;

        $dg = $this->getDataGenerator();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);

        // Only student role is expected.
        $roles = helper::get_user_course_roles();
        $expected = [$studentrole->id => 'student'];
        $this->assertEquals($expected, $roles);

        // Test with 2 roles, student and teacher role is expected.
        $dg->enrol_user($this->user->id, $this->course->id, 'teacher');
        $roles = helper::get_user_course_roles();
        $expected = [$studentrole->id => 'student', $teacherrole->id => 'teacher'];
        $this->assertEquals($expected, $roles);

        // If we switch role to student we should still have the course roles.
        role_switch($studentrole->id, context_course::instance($this->course->id));
        $roles = helper::get_user_course_roles();
        $expected = [$studentrole->id => 'student', $teacherrole->id => 'teacher'];
        $this->assertEquals($expected, $roles);
    }

    /**
     * Test that get_user_switched_course_role returns correct role.
     */
    public function test_get_user_switched_course_role() {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);

        // Right now we are not switched, expected 0.
        $roleid = helper::get_user_switched_course_role();
        $this->assertEquals(0, $roleid);

        // Switch to student and check the roleid returned matches the student role.
        role_switch($studentrole->id, context_course::instance($this->course->id));
        $roleid = helper::get_user_switched_course_role();
        $this->assertEquals($studentrole->id, $roleid);
    }

    /**
     * Test that get_banner_html returns the expected HTML.
     * This essentially tests local_switchrolebanner\output\banner too.
     */
    public function test_get_banner_html() {
        global $DB, $PAGE, $OUTPUT;

        $dg = $this->getDataGenerator();
        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
        $studentswitchparam = '<input type="hidden" name="switchrole" value="' . $studentrole->id . '">';
        $teacherswitchparam = '<input type="hidden" name="switchrole" value="' . $teacherrole->id . '">';

        // Expecting admin string with only student role switch button.
        $html = helper::get_banner_html();
        $infomessage = get_string('viewingasadmin', 'local_switchrolebanner');
        $this->assertStringContainsString($infomessage, $html);
        $this->assertStringContainsString($studentswitchparam, $html);
        $this->assertStringContainsString('Student', $html);
        $this->assertStringNotContainsString($teacherswitchparam, $html);
        $this->assertStringNotContainsString('Non-editing teacher', $html);

        // If we add teacher role we should expect to see both switch buttons for student and teacher.
        $dg->enrol_user($this->user->id, $this->course->id, 'teacher');
        $html = helper::get_banner_html();
        $infomessage = get_string('viewingasadmin', 'local_switchrolebanner');
        $this->assertStringContainsString($infomessage, $html);
        $this->assertStringContainsString($studentswitchparam, $html);
        $this->assertStringContainsString('Student', $html);
        $this->assertStringContainsString($teacherswitchparam, $html);
        $this->assertStringContainsString('Non-editing teacher', $html);

        // When switched to student the message should say user is viewing as student and have a
        // return to regular role button. Student and teacher switch buttons should not exist.
        role_switch($studentrole->id, context_course::instance($this->course->id));
        $html = helper::get_banner_html();
        $infomessage = get_string('viewingasrole', 'local_switchrolebanner', 'Student');
        $this->assertStringContainsString($infomessage, $html);
        $switchroleparam = '<input type="hidden" name="switchrole" value="0">';
        $this->assertStringContainsString($switchroleparam, $html);
        $label = get_string('switchrolereturn');
        $this->assertStringContainsString($label, $html);
        $this->assertStringNotContainsString($studentswitchparam, $html);
        $this->assertStringNotContainsString($teacherswitchparam, $html);

        // Create a new admin user and enable self enrol method for course.
        $user2 = $dg->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);
        $dg->role_assign($managerrole->id, $user2->id, context_system::instance()->id);

        $selfplugin = enrol_get_plugin('self');
        $instance = $DB->get_record('enrol', ['courseid' => $this->course->id, 'enrol' => 'self'], '*', MUST_EXIST);
        $selfplugin->update_status($instance, ENROL_INSTANCE_ENABLED);

        // Switch to new admin user with no course roles, expect to see self enrol message and button.
        $this->setUser($user2);
        $html = helper::get_banner_html();
        $infomessage = get_string('canselfenrol', 'local_switchrolebanner', 'Student');
        $this->assertStringContainsString($infomessage, $html);
        $enorlidparam = '<input type="hidden" name="id" value="' . $this->course->id . '">';
        $this->assertStringContainsString($enorlidparam, $html);
        $this->assertStringContainsString('enrol/index.php', $html);
        $label = get_string('enrolme', 'core_enrol');
        $this->assertStringContainsString($label, $html);
    }

    /**
     * Test that handle_role_switch works.
     * This also tests set_user_last_role and get_user_last_role.
     */
    public function test_handle_role_switch() {
        global $DB;

        $studentrole = $DB->get_record('role', ['shortname' => 'student']);
        $teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);

        // Nothing should happen by default.
        helper::handle_role_switch();
        $this->assertFalse(is_role_switched($this->course->id));

        // Test set_user_last_role and get_user_last_role are working correctly.
        // Should return 0 by default.
        $roleid = helper::get_user_last_role();
        $this->assertEquals(0, $roleid);
        helper::set_user_last_role($studentrole->id);
        $this->assertEquals($studentrole->id, helper::get_user_last_role());
        helper::set_user_last_role(0);
        $this->assertEquals(0, helper::get_user_last_role());

        // Replicate the switchrole param with a role the user doesn't have in the course,
        // this should not set the roleid as a user preference.
        $_POST['switchrole'] = $teacherrole->id;
        helper::handle_role_switch();
        $this->assertEquals(0, helper::get_user_last_role());
        unset($_POST['switchrole']);

        // Replicate the switchrole param with a role the user has in the course,
        // this should set the roleid as a user preference.
        $_POST['switchrole'] = $studentrole->id;
        helper::handle_role_switch();
        $this->assertEquals($studentrole->id, helper::get_user_last_role());
        unset($_POST['switchrole']);

        // Test it auto switches role now that we have a last switched role set.
        $this->assertFalse(is_role_switched($this->course->id));
        helper::handle_role_switch();
        $this->assertTrue(is_role_switched($this->course->id));
        role_switch(0, context_course::instance($this->course->id));

        // Replicate if the last switched role is no longer valid for this plugin,
        // this should just end up doing nothing and clearing the preference.
        $this->assertFalse(is_role_switched($this->course->id));
        helper::set_user_last_role($teacherrole->id);
        $this->assertEquals($teacherrole->id, helper::get_user_last_role());
        helper::handle_role_switch();
        $this->assertFalse(is_role_switched($this->course->id));
        $this->assertEquals(0, helper::get_user_last_role());
    }

    /**
     * Test that is_banner_hidden returns correctly.
     * This also tests hide_banner and local_switchrolebanner_hide_banner external function.
     */
    public function test_is_banner_hidden() {
        global $PAGE;

        $dg = $this->getDataGenerator();
        $course1 = $this->course;
        $course2 = $dg->create_course();
        $course3 = $dg->create_course();

        // By default should not be hidden.
        $this->assertFalse(helper::is_banner_hidden());

        // Workaround for external_api::call_external_function requiring sesskey.
        $_POST['sesskey'] = sesskey();
        $function = 'local_switchrolebanner_hide_banner';

        // Test the local_switchrolebanner_hide_banner external function, banner should now be hidden.
        $args = ['courseid' => $course1->id];
        $result = external_api::call_external_function($function, $args);
        $this->assertTrue(helper::is_banner_hidden());

        // Test hiding banner for another course too.
        $PAGE->set_course($course2);
        $this->assertFalse(helper::is_banner_hidden());
        $args = ['courseid' => $course2->id];
        $result = external_api::call_external_function($function, $args);
        $this->assertTrue(helper::is_banner_hidden());

        // Test a 3rd course is still visible now that 2 are hidden.
        $PAGE->set_course($course3);
        $this->assertFalse(helper::is_banner_hidden());

        // Now test all 3 remain hidden if we have 3 hidden courses now
        // (a comma deliminated string with a start, middle, and end).
        $args = ['courseid' => $course3->id];
        $result = external_api::call_external_function($function, $args);
        $PAGE->set_course($course1);
        $this->assertTrue(helper::is_banner_hidden());
        $PAGE->set_course($course2);
        $this->assertTrue(helper::is_banner_hidden());
        $PAGE->set_course($course3);
        $this->assertTrue(helper::is_banner_hidden());
    }

    
}
