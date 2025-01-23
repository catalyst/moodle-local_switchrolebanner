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

namespace local_switchrolebanner;

use context_system;

/**
 * Class hook_callbacks_test.
 *
 * @package    local_switchrolebanner
 * @author     Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright  2025 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \local_switchrolebanner\hook_callbacks
 */
final class hook_callbacks_test extends \advanced_testcase {
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

        set_config('enabled', 1, 'local_switchrolebanner');

        $this->course = $this->getDataGenerator()->create_course();
        $this->user = $this->getDataGenerator()->create_user();
        $managerrole = $DB->get_record('role', ['shortname' => 'manager']);

        $this->getDataGenerator()->role_assign($managerrole->id, $this->user->id, context_system::instance()->id);
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, 'student');

        $PAGE->set_course($this->course);
        $PAGE->set_url('/course/view.php', ['id' => $this->course->id]);
        $this->setUser($this->user);
    }

    /**
     * Check that callback is executed and inserts the html on page load.
     *
     * @covers ::before_footer_html_generation
     */
    public function test_before_footer_html_generation(): void {
        $this->resetAfterTest(true);
        $page = new \moodle_page();

        $page->set_state(\moodle_page::STATE_PRINTING_HEADER);
        $page->set_state(\moodle_page::STATE_IN_BODY);
        $page->opencontainers->push('header/footer', '</body></html>');

        $renderer = new \core_renderer($page, RENDERER_TARGET_GENERAL);

        $footer = $renderer->footer();
        $this->assertIsString($footer);

        $infomessage = get_string('viewingasadmin', 'local_switchrolebanner');

        $this->assertStringContainsString($infomessage, $footer);
    }
}
