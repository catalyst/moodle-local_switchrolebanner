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

use core\hook\output\before_footer_html_generation;
use local_switchrolebanner\helper;

/**
 * Hook callbacks for Switch role banner.
 *
 * @package    local_switchrolebanner
 * @author     Scott Verbeek <scottverbeek@catalyst-au.net>
 * @copyright  2025 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Bootstrap the add the banner to the page if conditions are correct.
     *
     * @param before_footer_html_generation $hook
     * @return void
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        if (!helper::should_show_banner()) {
            return;
        }

        $hook->add_html(helper::get_banner_html());
    }
}
