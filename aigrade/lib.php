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
 * Library functions for local_aigrade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add AI grading fields to assignment settings form
 *
 * @param moodleform_mod $formwrapper
 * @param MoodleQuickForm $mform
 */
function local_aigrade_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB;
    
    // Only add to assignment module
    if ($formwrapper->get_current()->modulename !== 'assign') {
        return;
    }
    
    // Get the assignment ID if editing
    $update = optional_param('update', 0, PARAM_INT);
    $aiconfig = null;
    
    if ($update) {
        $cm = get_coursemodule_from_id('assign', $update);
        if ($cm) {
            $aiconfig = $DB->get_record('local_aigrade_config', ['assignmentid' => $cm->instance]);
        }
    }
    
    // Add AI Grading section
    $mform->addElement('header', 'aigrade_header', get_string('aigrade', 'local_aigrade'));
    
    // Add warning message
    $mform->addElement('static', 'aigrade_warning', '', get_string('aigrade_warning_text', 'local_aigrade'));
        
    // Enable AI grading checkbox
    $mform->addElement('advcheckbox', 'aigrade_enabled', get_string('aigrade', 'local_aigrade'));
    $mform->addHelpButton('aigrade_enabled', 'aigrade', 'local_aigrade');
    $mform->setDefault('aigrade_enabled', $aiconfig ? $aiconfig->enabled : 0);
    
    // Grade level selection
    $grade_options = array(
        '3' => 'Grade 3',
        '4' => 'Grade 4',
        '5' => 'Grade 5',
        '6' => 'Grade 6',
        '7' => 'Grade 7',
        '8' => 'Grade 8',
        '9' => 'Grade 9 (Freshman)',
        '10' => 'Grade 10 (Sophomore)',
        '11' => 'Grade 11 (Junior)',
        '12' => 'Grade 12 (Senior)',
    );
    $mform->addElement('select', 'aigrade_grade_level', 
        get_string('grade_level', 'local_aigrade'), 
        $grade_options);
    $mform->addHelpButton('aigrade_grade_level', 'grade_level', 'local_aigrade');
    $mform->disabledIf('aigrade_grade_level', 'aigrade_enabled');
    $mform->setDefault('aigrade_grade_level', $aiconfig && $aiconfig->grade_level ? $aiconfig->grade_level : '9');
    
    // AI grading instructions WITH rubric
    $mform->addElement('textarea', 'aigrade_instructions_with_rubric', 
        get_string('aigrade_instructions_with_rubric_field', 'local_aigrade'), 
        'wrap="virtual" rows="8" cols="50"');
    $mform->setType('aigrade_instructions_with_rubric', PARAM_TEXT);
    $mform->addHelpButton('aigrade_instructions_with_rubric', 'aigrade_instructions_with_rubric_field', 'local_aigrade');
    $mform->disabledIf('aigrade_instructions_with_rubric', 'aigrade_enabled');
    
    $default_with_rubric = get_config('local_aigrade', 'default_instructions_with_rubric');
    $mform->setDefault('aigrade_instructions_with_rubric', 
        $aiconfig && $aiconfig->instructions_with_rubric ? $aiconfig->instructions_with_rubric : $default_with_rubric);
    
    // AI grading instructions WITHOUT rubric
    $mform->addElement('textarea', 'aigrade_instructions_without_rubric', 
        get_string('aigrade_instructions_without_rubric_field', 'local_aigrade'), 
        'wrap="virtual" rows="8" cols="50"');
    $mform->setType('aigrade_instructions_without_rubric', PARAM_TEXT);
    $mform->addHelpButton('aigrade_instructions_without_rubric', 'aigrade_instructions_without_rubric_field', 'local_aigrade');
    $mform->disabledIf('aigrade_instructions_without_rubric', 'aigrade_enabled');
    
    $default_without_rubric = get_config('local_aigrade', 'default_instructions_without_rubric');
    $mform->setDefault('aigrade_instructions_without_rubric', 
        $aiconfig && $aiconfig->instructions_without_rubric ? $aiconfig->instructions_without_rubric : $default_without_rubric);
    
    // Rubric file upload
    $mform->addElement('filemanager', 'aigrade_rubric', 
        get_string('aigrade_rubric', 'local_aigrade'), 
        null,
        array(
            'subdirs' => 0,
            'maxbytes' => 10485760, // 10MB
            'maxfiles' => 1,
            'accepted_types' => array('.pdf', '.txt', '.docx', '.doc')
        ));
    $mform->addHelpButton('aigrade_rubric', 'aigrade_rubric', 'local_aigrade');
    $mform->disabledIf('aigrade_rubric', 'aigrade_enabled');
}

/**
 * Save AI grading configuration when assignment is saved
 *
 * @param stdClass $data
 * @param stdClass $course
 */
function local_aigrade_coursemodule_edit_post_actions($data, $course) {
    global $DB;
    
    // Only process assignment module
    if ($data->modulename !== 'assign') {
        return $data;
    }
    
    // Get the assignment instance ID
    if (!isset($data->instance)) {
        return $data;
    }
    
    $assignmentid = $data->instance;
    
    // Check if config exists
    $config = $DB->get_record('local_aigrade_config', ['assignmentid' => $assignmentid]);
    
    $now = time();
    
    if ($config) {
        // Update existing config
        $config->enabled = isset($data->aigrade_enabled) ? $data->aigrade_enabled : 0;
        $config->instructions_with_rubric = isset($data->aigrade_instructions_with_rubric) ? $data->aigrade_instructions_with_rubric : '';
        $config->instructions_without_rubric = isset($data->aigrade_instructions_without_rubric) ? $data->aigrade_instructions_without_rubric : '';
        $config->grade_level = isset($data->aigrade_grade_level) ? $data->aigrade_grade_level : '9';
        $config->timemodified = $now;
        $DB->update_record('local_aigrade_config', $config);
    } else {
        // Create new config
        $config = new stdClass();
        $config->assignmentid = $assignmentid;
        $config->enabled = isset($data->aigrade_enabled) ? $data->aigrade_enabled : 0;
        $config->instructions_with_rubric = isset($data->aigrade_instructions_with_rubric) ? $data->aigrade_instructions_with_rubric : '';
        $config->instructions_without_rubric = isset($data->aigrade_instructions_without_rubric) ? $data->aigrade_instructions_without_rubric : '';
        $config->grade_level = isset($data->aigrade_grade_level) ? $data->aigrade_grade_level : '9';
        $config->timecreated = $now;
        $config->timemodified = $now;
        $config->id = $DB->insert_record('local_aigrade_config', $config);
    }
    
    // Handle file upload (only if coursemodule exists - not available during initial creation)
    if (isset($data->aigrade_rubric) && isset($data->coursemodule) && $data->coursemodule > 0) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->aigrade_rubric,
            $context->id,
            'local_aigrade',
            'rubric',
            $assignmentid,
            array('subdirs' => 0, 'maxfiles' => 1)
        );
    }
    
    return $data;
}

/**
 * Callback to inject file data into the form
 * This runs when the form loads existing data
 */
function local_aigrade_coursemodule_definition_after_data($formwrapper) {
    global $DB;
    
    $current = $formwrapper->get_current();
    
    // Only for assignment module
    if (!isset($current->modulename) || $current->modulename !== 'assign') {
        return;
    }
    
    // Only when editing existing assignment (not during initial creation)
    if (!isset($current->coursemodule) || !isset($current->instance) || empty($current->coursemodule) || $current->coursemodule <= 0) {
        return;
    }
    
    $assignmentid = $current->instance;
    $context = \context_module::instance($current->coursemodule);
    
    // Prepare the draft area
    $draftitemid = file_get_submitted_draft_itemid('aigrade_rubric');
    
    file_prepare_draft_area(
        $draftitemid,
        $context->id,
        'local_aigrade',
        'rubric',
        $assignmentid,
        array('subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 10485760)
    );
    
    // Load existing config data
    $aiconfig = $DB->get_record('local_aigrade_config', ['assignmentid' => $assignmentid]);
    
    $data_to_set = array('aigrade_rubric' => $draftitemid);
    
    if ($aiconfig) {
        // Only set if there's actual saved data (not empty)
        if (!empty($aiconfig->instructions_with_rubric)) {
            $data_to_set['aigrade_instructions_with_rubric'] = $aiconfig->instructions_with_rubric;
        }
        if (!empty($aiconfig->instructions_without_rubric)) {
            $data_to_set['aigrade_instructions_without_rubric'] = $aiconfig->instructions_without_rubric;
        }
    }
    
    // Set the values (only the rubric file, not overriding instruction defaults)
    $formwrapper->set_data($data_to_set);
}

/**
 * Inject AI Grade button into pages
 */
function local_aigrade_before_standard_top_of_body_html() {
    global $PAGE, $DB;

    // Only on assignment pages
    if (strpos($PAGE->pagetype, 'mod-assign') === false) {
        return '';
    }

    $cm = $PAGE->cm;
    if (!$cm || $cm->modname !== 'assign') {
        return '';
    }

    $context = context_module::instance($cm->id);
    if (!has_capability('local/aigrade:grade', $context)) {
        return '';
    }

    // Check if AI grading is enabled
    $aiconfig = $DB->get_record('local_aigrade_config', ['assignmentid' => $cm->instance]);
    if (!$aiconfig || !$aiconfig->enabled) {
        return '';
    }

    // Check if this is individual grading page
    $userid = optional_param('userid', 0, PARAM_INT);
    $is_individual = ($userid > 0);
    
    // Get custom AI name
    $ai_name = get_config('local_aigrade', 'ai_name');
    if (empty($ai_name)) {
        $ai_name = 'AI';
    }
    
    // Create appropriate URL and button text
    if ($is_individual) {
        $url = new moodle_url('/local/aigrade/grade_single.php', ['id' => $cm->id, 'userid' => $userid]);
        $button_text = get_string('button_grade_single', 'local_aigrade', $ai_name);
    } else {
        $url = new moodle_url('/local/aigrade/grade.php', ['id' => $cm->id]);
        $button_text = get_string('button_grade_bulk', 'local_aigrade', $ai_name);
    }
    
    // For individual grading, use JavaScript to insert near grade section
    if ($is_individual) {
        $buttonurl = $url->out(false);
        $buttontext = $button_text;
        
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                var checkCount = 0;
                var maxChecks = 20; // Check up to 20 times (10 seconds)
                
                var insertButton = function() {
                    checkCount++;
                    
                    // If button already exists, stop checking
                    if ($('.aigrade-single-button').length > 0) {
                        return true;
                    }
                    
                    // Try to find the Grade heading or input
                    var gradeHeading = $('h3:contains(\"Grade\"), h2:contains(\"Grade\")');
                    var gradeInput = $('input[name*=\"grade\"]');
                    
                    if (gradeHeading.length === 0 && gradeInput.length === 0 && checkCount < maxChecks) {
                        // Keep checking if elements aren't ready yet
                        return false;
                    }
                    
                    // Create button with AJAX functionality
                    var button = $('<button>')
                        .attr('type', 'button')
                        .addClass('btn btn-primary aigrade-single-button')
                        .css({'margin': '10px 0', 'display': 'inline-block'})
                        .text('" . addslashes($buttontext) . "')
                        .on('click', function() {
                            var btn = $(this);
                            var originalText = btn.text();
                            
                            // Disable button and show loading
                            btn.prop('disabled', true).text('Grading...');
                            
                            // Make AJAX call
                            $.ajax({
                                url: '" . $buttonurl . "&action=grade&sesskey=' + M.cfg.sesskey,
                                method: 'POST',
                                dataType: 'json',
                                success: function(response) {
                                    if (response.success) {
                                        // Reload the current grading page to show the new grade/feedback
                                        window.location.reload();
                                    } else {
                                        alert('Error: ' + (response.error || 'Unknown error occurred'));
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
                        .addClass('aigrade-button-container')
                        .css({
                            'margin': '15px 0',
                            'padding': '10px',
                            'background-color': '#d9edf7',
                            'border': '1px solid #bce8f1',
                            'border-radius': '4px'
                        })
                        .append(button);
                    
                    var inserted = false;
                    
                    if (gradeHeading.length) {
                        gradeHeading.first().after(container);
                        inserted = true;
                    }
                    
                    if (!inserted && gradeInput.length) {
                        gradeInput.first().closest('.fitem, .form-group').before(container);
                        inserted = true;
                    }
                    
                    if (!inserted && $('[data-region=\"grade-panel\"]').length) {
                        $('[data-region=\"grade-panel\"]').prepend(container);
                        inserted = true;
                    }
                    
                    if (!inserted) {
                        var mainContent = $('#region-main-box, #region-main, [role=\"main\"]').first();
                        if (mainContent.length) {
                            mainContent.prepend(container);
                            inserted = true;
                        }
                    }
                    
                    return inserted;
                };
                
                // Try inserting immediately and keep trying every 500ms
                var insertInterval = setInterval(function() {
                    if (insertButton() || checkCount >= maxChecks) {
                        clearInterval(insertInterval);
                    }
                }, 500);
                
                // Also try on various events
                $(window).on('load', insertButton);
                $(document).ajaxComplete(function() {
                    setTimeout(insertButton, 300);
                });
            });
        ");
        return '';
    }
    
    // For bulk grading, use JavaScript with AJAX
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            $(document).ready(function() {
                // Check if button already exists
                if ($('.aigrade-button-injected').length > 0) {
                    return;
                }
                
                // Create button with AJAX functionality
                var button = $('<button>')
                    .attr('type', 'button')
                    .addClass('btn btn-primary aigrade-button-injected')
                    .css({'margin': '10px 5px', 'display': 'inline-block'})
                    .text('" . $button_text . "')
                    .on('click', function() {
                        var btn = $(this);
                        var originalText = btn.text();
                        
                        // Confirm before grading all
                        if (!confirm('Grade all ungraded submissions with AI? This may take a few moments.')) {
                            return;
                        }
                        
                        // Disable button and show loading
                        btn.prop('disabled', true).text('Grading all submissions...');
                        
                        // Make AJAX call
                        $.ajax({
                            url: '" . $url->out(false) . "&action=grade&sesskey=' + M.cfg.sesskey,
                            method: 'POST',
                            dataType: 'json',
                            success: function(response) {
                                if (response.success) {
                                    alert('Successfully graded ' + response.count + ' submission(s).');
                                    // Reload the page to show updated grades
                                    window.location.reload();
                                } else {
                                    alert('Error: ' + (response.error || 'Unknown error occurred'));
                                    btn.prop('disabled', false).text(originalText);
                                }
                            },
                            error: function(xhr, status, error) {
                                alert('Error communicating with server: ' + error);
                                btn.prop('disabled', false).text(originalText);
                            }
                        });
                    });
                
                // Insert at top of submissions list
                if ($('.tertiary-navigation').length) {
                    $('.tertiary-navigation').first().prepend($('<div>').css('display', 'inline-block').append(button));
                } else if ($('#page-header').length) {
                    $('#page-header').first().after($('<div>').addClass('alert alert-info').css('margin', '15px').append(button));
                } else {
                    $('#page-content').prepend($('<div>').addClass('alert alert-info').css('margin', '15px').append(button));
                }
            });
        });
    ");

    return '';
}
