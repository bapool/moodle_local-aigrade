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
 * AMD module for AI Grade single submission button
 *
 * @module     local_aigrade/grade_single
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    return {
        /**
         * Initialize the grade single button
         * @param {string} buttonUrl The URL for the AJAX request
         * @param {string} buttonText The text to display on the button
         * @param {string} sesskey The session key
         */
        init: function(buttonUrl, buttonText, sesskey) {

            var insertButton = function() {
                // Don't insert if button already exists
                if ($('.aigrade-single-button').length > 0) {
                    return true;
                }

                // Create the button
                var button = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-primary aigrade-single-button')
                    .text(buttonText)
                    .data('force-regrade', false)
                    .on('click', function() {
                        var btn = $(this);
                        var originalText = btn.text();
                        var forceRegrade = btn.data('force-regrade');

                        btn.prop('disabled', true).text('Grading...');

                        // Get userid from current page URL
            var urlParams = new URLSearchParams(window.location.search);
            var currentUserid = urlParams.get('userid');

            var url = buttonUrl.replace('userid=0', 'userid=' + currentUserid) + '&action=grade&sesskey=' + sesskey;
                        if (forceRegrade) {
                            url += '&force_regrade=1';
                        }

                        $.ajax({
                            url: url,
                            method: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    window.location.reload();
                                } else if (response.already_graded) {
                                    // Submission is already graded
                                    btn.prop('disabled', false);
                                    btn.text('Regrade with AI');
                                    btn.removeClass('btn-primary').addClass('btn-warning');
                                    btn.data('force-regrade', true);

                                    if (confirm('This submission is already graded. Do you want to regrade it with AI?')) {
                                        // User confirmed, trigger another click
                                        btn.click();
                                    }
                                } else {
                                    var errorMsg = response.error || 'Unknown error occurred';
                                    alert('Error: ' + errorMsg);
                                    btn.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function(xhr, status, error) {
                                alert('Error communicating with server: ' + error);
                                btn.prop('disabled', false).text(originalText);
                            }
                        });
                    });

                var container = $('<div>')
                    .addClass('local_aigrade-button-container')
                    .append(button);

                // Try multiple insertion points in order of preference
                var inserted = false;

                // Option 1: After grade input row (preferred - puts it right after the grade field)
                var gradeInput = $('input[name="grade"]');
                if (gradeInput.length) {
                    var gradeRow = gradeInput.closest('.row, .fitem');
                    if (gradeRow.length) {
                        gradeRow.after(container);
                        inserted = true;
                    }
                }

                // Option 2: Before feedback section (if grade input not found)
                if (!inserted) {
                    var feedbackSection = $('div[id*="fitem_id_assignfeedbackcomments"]');
                    if (feedbackSection.length) {
                        feedbackSection.before(container);
                        inserted = true;
                    }
                }

                // Option 3: Inside grade panel
                if (!inserted) {
                    var gradePanel = $('[data-region="grade-panel"]');
                    if (gradePanel.length) {
                        gradePanel.prepend(container);
                        inserted = true;
                    }
                }

                // Option 4: Fallback to main content area
                if (!inserted) {
                    var mainContent = $('#region-main-box, #region-main, [role="main"]').first();
                    if (mainContent.length) {
                        mainContent.prepend(container);
                        inserted = true;
                    }
                }

                return inserted;
            };

            // Try immediately when script loads
            setTimeout(insertButton, 100);

            // Wait for DOM to be ready
            $(document).ready(function() {
                setTimeout(insertButton, 100);
            });

            // Wait for full page load
            $(window).on('load', function() {
                setTimeout(insertButton, 200);
            });

            // Set up MutationObserver to watch for dynamic content
            var observer = new MutationObserver(function() {
                insertButton();
            });

            // Start observing the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            // Also re-insert after AJAX requests complete
            $(document).ajaxComplete(function() {
                setTimeout(insertButton, 100);
            });

            // Periodic checking as final fallback
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
