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

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    return {
        /**
         * Initialize the grade bulk button
         * @param {int} cmid The course module ID
         * @param {string} buttonText The text to display on the button
         */
        init: function(cmid, buttonText) {

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

                        Str.get_strings([
                            {key: 'confirm_bulk_grade', component: 'local_aigrade'},
                            {key: 'grading_in_progress', component: 'local_aigrade'},
                            {key: 'success_graded_count', component: 'local_aigrade'},
                            {key: 'error_with_message', component: 'local_aigrade'},
                            {key: 'error_unknown', component: 'local_aigrade'},
                            {key: 'error_server_communication', component: 'local_aigrade'}
                        ]).done(function(strings) {
                            if (!confirm(strings[0])) {
                                return;
                            }

                            btn.prop('disabled', true).text(strings[1]);

                            Ajax.call([{
                                methodname: 'local_aigrade_grade_bulk',
                                args: {
                                    cmid: cmid
                                }
                            }])[0].done(function(response) {
                                if (response.success) {
                                    Notification.addNotification({
                                        message: strings[2].replace('{$a}', response.count),
                                        type: 'success'
                                    });
                                    window.location.reload();
                                } else {
                                    var errorMsg = response.error || strings[4];
                                    Notification.addNotification({
                                        message: strings[3].replace('{$a}', errorMsg),
                                        type: 'error'
                                    });
                                    btn.prop('disabled', false).text(originalText);
                                }
                            }).fail(function(error) {
                                var errorMsg = error.message || strings[4];
                                Notification.addNotification({
                                    message: strings[5].replace('{$a}', errorMsg),
                                    type: 'error'
                                });
                                btn.prop('disabled', false).text(originalText);
                            });
                        }).fail(function() {
                            // Fallback if string fetch fails - use English defaults
                            if (!confirm('Grade all ungraded submissions with AI? This may take a few moments.')) {
                                return;
                            }

                            btn.prop('disabled', true).text('Grading all submissions...');

                            Ajax.call([{
                                methodname: 'local_aigrade_grade_bulk',
                                args: {
                                    cmid: cmid
                                }
                            }])[0].done(function(response) {
                                if (response.success) {
                                    alert('Successfully graded ' + response.count + ' submission(s).');
                                    window.location.reload();
                                } else {
                                    alert('Error: ' + (response.error || 'Unknown error occurred'));
                                    btn.prop('disabled', false).text(originalText);
                                }
                            }).fail(function(error) {
                                alert('Error communicating with server: ' + (error.message || 'Unknown error'));
                                btn.prop('disabled', false).text(originalText);
                            });
                        });
                    });

                // Try multiple insertion points
                var inserted = false;

                // Option 1: Tertiary navigation (preferred in Moodle 4.x)
                if ($('.tertiary-navigation').length) {
                    $('.tertiary-navigation').first().prepend($('<div>').css('display', 'inline-block').append(button));
                    inserted = true;
                }

                // Option 2: After page header
                if (!inserted && $('#page-header').length) {
                    $('#page-header').first().after($('<div>').addClass('alert alert-info').css('margin', '15px').append(button));
                    inserted = true;
                }

                // Option 3: Prepend to page content
                if (!inserted && $('#page-content').length) {
                    $('#page-content').prepend($('<div>').addClass('alert alert-info').css('margin', '15px').append(button));
                    inserted = true;
                }

                return inserted;
            };

            // Wait for DOM to be ready, then try to insert
            $(document).ready(function() {
                // Try immediately
                var inserted = insertButton();

                // If not inserted, set up interval checking
                if (!inserted) {
                    var checkCount = 0;
                    var maxChecks = 10; // Check for 5 seconds

                    var insertInterval = setInterval(function() {
                        checkCount++;
                        if (insertButton() || checkCount >= maxChecks) {
                            clearInterval(insertInterval);
                        }
                    }, 500);
                }
            });

            // Also try on window load as backup
            $(window).on('load', function() {
                insertButton();
            });
        }
    };
});
