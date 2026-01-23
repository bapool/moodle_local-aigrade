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
 * AMD module for AI Grade bulk grading button
 *
 * @module     local_aigrade/grade_bulk
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification'], function($, Notification) {

    return {
        /**
         * Initialize the grade bulk button
         * @param {string} buttonUrl The URL for the AJAX request
         * @param {string} buttonText The text to display on the button
         * @param {string} sesskey The session key
         */
        init: function(buttonUrl, buttonText, sesskey) {
            var insertButton = function() {
                // Don't insert if button already exists
                if ($('.aigrade-button-injected').length > 0) {
                    return true;
                }

                // Create the button
                var button = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-primary aigrade-button-injected')
                    .text(buttonText)
                    .on('click', function() {
                        var btn = $(this);
                        var originalText = btn.text();

                        if (!confirm('Grade all ungraded submissions with AI? This may take a few moments.')) {
                            return;
                        }

                        btn.prop('disabled', true).text('Grading all submissions...');

                        $.ajax({
                            url: buttonUrl + '&action=grade&sesskey=' + sesskey,
                            method: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    Notification.addNotification({
                                        message: 'Successfully graded ' + response.count + ' submission(s).',
                                        type: 'success'
                                    });
                                    window.location.reload();
                                } else {
                                    Notification.addNotification({
                                        message: 'Error: ' + (response.error || 'Unknown error occurred'),
                                        type: 'error'
                                    });
                                    btn.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function(xhr, status, error) {
                                Notification.addNotification({
                                    message: 'Error communicating with server: ' + error,
                                    type: 'error'
                                });
                                btn.prop('disabled', false).text(originalText);
                            }
                        });
                    });

                var container = $('<div>')
                    .addClass('mb-3')
                    .append(button);

                // Insert before the submissions table
                var submissionsTable = $('table.flexible, table.generaltable').first();
                if (submissionsTable.length) {
                    submissionsTable.before(container);
                    return true;
                }

                // Fallback: insert at top of main content
                var mainContent = $('#region-main').first();
                if (mainContent.length) {
                    mainContent.prepend(container);
                    return true;
                }

                return false;
            };

            // Try to insert button immediately
            setTimeout(insertButton, 100);

            // Try after DOM ready
            $(document).ready(function() {
                setTimeout(insertButton, 100);
            });

            // Try after window load
            $(window).on('load', function() {
                setTimeout(insertButton, 200);
            });

            // Periodic checking as fallback
            var checkCount = 0;
            var checkInterval = setInterval(function() {
                checkCount++;
                if (insertButton() || checkCount >= 20) {
                    clearInterval(checkInterval);
                }
            }, 500);
        }
    };
});
