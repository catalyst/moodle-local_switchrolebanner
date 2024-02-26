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
 * External API.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_switchrolebanner;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_external\external_api;
use core_external\external_description;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * External API class.
 *
 * @package    local_switchrolebanner
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function hide_banner_parameters() {
        return new external_function_parameters(
                [
                    'courseid' => new external_value(PARAM_INT, 'Course code', VALUE_REQUIRED),
                ]
        );
    }

    /**
     * Hides the banner for the passed course.
     *
     * @param int $courseid The course id to hide the banner for
     * @return array|bool if the banner was hidden
     */
    public static function hide_banner($courseid) {
        global $PAGE, $DB;

        $PAGE->set_context(context_course::instance($courseid));

        $params = self::validate_parameters(self::hide_banner_parameters(),
            [
                'courseid' => $courseid,
            ]
        );

        helper::hide_banner($courseid);

        return ['result' => true];
    }

    /**
     * Returns success result.
     *
     * @return external_description
     */
    public static function hide_banner_returns() {
        return new external_single_structure([
            'result' => new external_value(PARAM_BOOL, 'If the banner was hidden'),
        ]);
    }
}
