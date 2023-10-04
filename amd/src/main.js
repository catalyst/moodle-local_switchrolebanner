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
    'jquery',
    'core/ajax',
    'core/notification',
],
function(
    $,
    ajax,
    notification
) {

    /**
     * The banner element.
     */
    var banner;

    /**
     * The course id.
     */
    var courseId;

    /**
     * Moves the banner to the top of the page.
     */
    var moveBanner = function() {
        var pageHeader = $('#page-header');
        banner.insertBefore(pageHeader);
    };

    /**
     * Closes the banner.
     */
    var cloaseBanner = function() {
        banner.remove();
        document.body.classList.remove('local_switchrolebanner');
    };

    /**
     * Hides the banner for the session.
     */
    var hideBanner = function() {
        console.log(courseId);
        var request = {
                methodname: 'local_switchrolebanner_hide_banner',
                args: {courseid: courseId}
            },
            promise = ajax.call([request])[0];
        promise.then(function() {
            cloaseBanner();
            return promise;
        }).catch(notification.exception);
    };

    /**
     * Initialise the banner.
     *
     * @param {object} root The root element for the banner.
     * @param {int} course The id of the course.
     */
    var init = function(root, course) {
        banner = $(root);
        courseId = course;

        document.body.classList.add('local_switchrolebanner');
        moveBanner();
        banner.find('.close').on('click', function() {
            cloaseBanner();
        });
        banner.find('#switchrolebanner-hide-banner').on('change', function() {
            hideBanner();
        });
    };

    return {
        init: init
    };
});
