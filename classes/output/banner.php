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
 * Class containing data for the banner.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_switchrolebanner\output;

defined('MOODLE_INTERNAL') || die();

use local_switchrolebanner\helper;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for the banner.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class banner implements renderable, templatable {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) : array {
        global $OUTPUT, $PAGE;

        $switchedrole = helper::get_user_switched_course_role();
        $containerclasses = 'switchrolebanner-btncontainer mr-2 mb-1';

        if (!empty($switchedrole)) {
            $rolenames = role_get_names();
            $rolename = $rolenames[$switchedrole]->localname;
            $infomessage = get_string('viewingasrole', 'local_switchrolebanner', $rolename);

            $url = new moodle_url('/course/switchrole.php', ['id' => $PAGE->course->id, 'switchrole' => 0, 'returnurl' => $PAGE->url]);
            $label = get_string('switchrolereturn');
            $buttonshtml = $OUTPUT->container($OUTPUT->single_button($url, htmlspecialchars_decode($label, ENT_COMPAT)), $containerclasses);
        } else {
            $infomessage = get_string('viewingasadmin', 'local_switchrolebanner');

            $buttonshtml = $OUTPUT->container(get_string('switchroleto'), $containerclasses);
            $switchablecourseroles = helper::get_user_switchable_course_roles();
            foreach ($switchablecourseroles as $key => $role) {
                $url = new moodle_url('/course/switchrole.php', ['id' => $PAGE->course->id, 'switchrole' => $key, 'returnurl' => $PAGE->url]);
                $buttonshtml .= $OUTPUT->container($OUTPUT->single_button($url, htmlspecialchars_decode($role, ENT_COMPAT)), $containerclasses);
            }
        }

        $data = [
            'courseid' => $PAGE->course->id,
            'infomessage' => $infomessage,
            'buttons' => $buttonshtml
        ];

        return $data;
    }
}