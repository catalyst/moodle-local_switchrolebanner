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
 * Javascript for the switch role banner.
 *
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2023 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery'
],
function(
    $
) {

    /**
     * Moves the banner to the top of the page.
     *
     * @param {object} banner The banner element.
     */
    var moveBanner = function(banner) {
        var pageHeader = $('#page-header');
        banner.insertBefore(pageHeader);
    };

    /**
     * Closes the banner.
     *
     * @param {object} banner The banner element.
     */
    var cloaseBanner = function(banner) {
        banner.remove();
        document.body.classList.remove('local_switchrolebanner');
    };

    /**
     * Initialise the banner.
     *
     * @param {object} root The root element for the banner.
     */
    var init = function(root) {
        var banner = $(root);

        document.body.classList.add('local_switchrolebanner');
        moveBanner(banner);
        banner.find('.close').on('click', function() {
            cloaseBanner(banner);
        });
    };

    return {
        init: init
    };
});
