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
 * Hook to extend the course module settings form (assignment settings)
 *
 * @param object $formwrapper The moodleform_mod instance
 * @param object $mform The MoodleQuickForm instance
 */
function local_aigrade_coursemodule_standard_elements($formwrapper, $mform) {
    global $DB, $PAGE;
    
    $current = $formwrapper->get_current();
    
    // Only for assignment module
    if (!isset($current->modulename) || $current->modulename !== 'assign') {
        return;
    }
    
    // Get existing config if editing
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
    if ($aiconfig) {
        $mform->setDefault('aigrade_enabled', $aiconfig->enabled);
    }
    
    // Grade level selector
    $grade_levels = [
        '3' => get_string('grade_level_3', 'local_aigrade'),
        '4' => get_string('grade_level_4', 'local_aigrade'),
        '5' => get_string('grade_level_5', 'local_aigrade'),
        '6' => get_string('grade_level_6', 'local_aigrade'),
        '7' => get_string('grade_level_7', 'local_aigrade'),
        '8' => get_string('grade_level_8', 'local_aigrade'),
        '9' => get_string('grade_level_9', 'local_aigrade'),
        '10' => get_string('grade_level_10', 'local_aigrade'),
        '11' => get_string('grade_level_11', 'local_aigrade'),
        '12' => get_string('grade_level_12', 'local_aigrade'),
    ];
    $mform->addElement('select', 'aigrade_grade_level', get_string('grade_level', 'local_aigrade'), $grade_levels);
    $mform->addHelpButton('aigrade_grade_level', 'grade_level', 'local_aigrade');
    $mform->disabledIf('aigrade_grade_level', 'aigrade_enabled');
    if ($aiconfig && isset($aiconfig->grade_level)) {
        $mform->setDefault('aigrade_grade_level', $aiconfig->grade_level);
    } else {
        $mform->setDefault('aigrade_grade_level', '9');
    }
    
    // Grading strictness selector
    $strictness_levels = [
        'very_lenient' => get_string('strictness_very_lenient', 'local_aigrade'),
        'lenient' => get_string('strictness_lenient', 'local_aigrade'),
        'standard' => get_string('strictness_standard', 'local_aigrade'),
        'rigorous' => get_string('strictness_rigorous', 'local_aigrade'),
        'very_rigorous' => get_string('strictness_very_rigorous', 'local_aigrade'),
    ];
    $mform->addElement('select', 'aigrade_grading_strictness', get_string('grading_strictness', 'local_aigrade'), $strictness_levels);
    $mform->addHelpButton('aigrade_grading_strictness', 'grading_strictness', 'local_aigrade');
    $mform->disabledIf('aigrade_grading_strictness', 'aigrade_enabled');
    if ($aiconfig && isset($aiconfig->grading_strictness)) {
        $mform->setDefault('aigrade_grading_strictness', $aiconfig->grading_strictness);
    } else {
        $mform->setDefault('aigrade_grading_strictness', 'standard');
    }
    
    // AI instructions WITH rubric
    $mform->addElement('textarea', 'aigrade_instructions_with_rubric', 
        get_string('aigrade_instructions_with_rubric_field', 'local_aigrade'),
        'rows="8" cols="80"');
    $mform->addHelpButton('aigrade_instructions_with_rubric', 'aigrade_instructions_with_rubric_field', 'local_aigrade');
    $mform->disabledIf('aigrade_instructions_with_rubric', 'aigrade_enabled');
    if ($aiconfig && !empty($aiconfig->instructions_with_rubric)) {
        $mform->setDefault('aigrade_instructions_with_rubric', $aiconfig->instructions_with_rubric);
    } else {
        $default = get_config('local_aigrade', 'default_instructions_with_rubric');
        $mform->setDefault('aigrade_instructions_with_rubric', $default);
    }
    
    // AI instructions WITHOUT rubric
    $mform->addElement('textarea', 'aigrade_instructions_without_rubric', 
        get_string('aigrade_instructions_without_rubric_field', 'local_aigrade'),
        'rows="8" cols="80"');
    $mform->addHelpButton('aigrade_instructions_without_rubric', 'aigrade_instructions_without_rubric_field', 'local_aigrade');
    $mform->disabledIf('aigrade_instructions_without_rubric', 'aigrade_enabled');
    if ($aiconfig && !empty($aiconfig->instructions_without_rubric)) {
        $mform->setDefault('aigrade_instructions_without_rubric', $aiconfig->instructions_without_rubric);
    } else {
        $default = get_config('local_aigrade', 'default_instructions_without_rubric');
        $mform->setDefault('aigrade_instructions_without_rubric', $default);
    }
    
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
 * Hook to populate form data when editing
 *
 * @param object $formwrapper
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
    $config = $DB->get_record('local_aigrade_config', ['assignmentid' => $assignmentid]);
    
    if ($config) {
        $formwrapper->set_data(array('aigrade_rubric' => $draftitemid));
    }
}

/**
 * Hook to save AI grading configuration when assignment is saved
 *
 * @param object $data
 * @param object $course
 * @return object Modified data
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
    
    $record = new stdClass();
    $record->assignmentid = $assignmentid;
    $record->enabled = isset($data->aigrade_enabled) ? $data->aigrade_enabled : 0;
    $record->grade_level = isset($data->aigrade_grade_level) ? $data->aigrade_grade_level : '9';
    $record->grading_strictness = isset($data->aigrade_grading_strictness) ? $data->aigrade_grading_strictness : 'standard';
    $record->instructions_with_rubric = isset($data->aigrade_instructions_with_rubric) ? $data->aigrade_instructions_with_rubric : '';
    $record->instructions_without_rubric = isset($data->aigrade_instructions_without_rubric) ? $data->aigrade_instructions_without_rubric : '';
    $record->timemodified = time();
    
    if ($config) {
        $record->id = $config->id;
        $DB->update_record('local_aigrade_config', $record);
    } else {
        $record->timecreated = time();
        $DB->insert_record('local_aigrade_config', $record);
    }
    
    // Handle file upload
    if (isset($data->aigrade_rubric)) {
        $context = context_module::instance($data->coursemodule);
        file_save_draft_area_files(
            $data->aigrade_rubric,
            $context->id,
            'local_aigrade',
            'rubric',
            $assignmentid,
            array('subdirs' => 0, 'maxfiles' => 1, 'maxbytes' => 10485760)
        );
    }
    
    return $data;
}

/**
 * Hook to inject AI grading buttons into assignment grading pages
 *
 * @return string Empty string (JavaScript loaded via AMD)
 */
function local_aigrade_before_footer() {
    global $PAGE, $DB;
        
    // TEMPORARY DEBUG
    //error_log('AI Grade Debug - Page type: ' . $PAGE->pagetype);
    //error_log('AI Grade Debug - Module name: ' . ($PAGE->cm ? $PAGE->cm->modname : 'no cm'));

    // Only on assignment module pages
    if ($PAGE->pagetype !== 'mod-assign-view' && 
        $PAGE->pagetype !== 'mod-assign-grader' &&
        $PAGE->pagetype !== 'mod-assign-grading' &&
        strpos($PAGE->pagetype, 'mod-assign') === false) {
        return '';
    }
    
    // Get course module
    $cm = $PAGE->cm;
    if (!$cm || $cm->modname !== 'assign') {
        return '';
    }
    
    // Check if AI Grade is enabled for this assignment
    $config = $DB->get_record('local_aigrade_config', ['assignmentid' => $cm->instance]);
    if (!$config || !$config->enabled) {
        return '';
    }
    
    // Check capability
    $context = context_module::instance($cm->id);
    if (!has_capability('local/aigrade:grade', $context)) {
        return '';
    }
    
    // Determine if we're on individual grading page or bulk grading page
    $action = optional_param('action', '', PARAM_ALPHA);
    $userid = optional_param('userid', 0, PARAM_INT);
    // Load individual grading script if action is 'grader', even without userid yet
    // (userid gets added via AJAX on initial page load)
    $is_individual = ($action === 'grader');
    
    // TEMPORARY DEBUG
    //error_log('AI Grade Debug - Action: ' . $action);
    //error_log('AI Grade Debug - UserID: ' . $userid);
    //error_log('AI Grade Debug - Is Individual: ' . ($is_individual ? 'YES' : 'NO'));
    
    // Get custom AI name
    $ai_name = get_config('local_aigrade', 'ai_name');
    if (empty($ai_name)) {
        $ai_name = 'AI';
    }
    
    // Load appropriate AMD module
    if ($is_individual) {
        $button_text = get_string('button_grade_single', 'local_aigrade', $ai_name);
        
        $PAGE->requires->js_call_amd('local_aigrade/grade_single', 'init', [
            $cm->id,
            $userid,
            $button_text
        ]);
    } else {
        $button_text = get_string('button_grade_bulk', 'local_aigrade', $ai_name);
        
        $PAGE->requires->js_call_amd('local_aigrade/grade_bulk', 'init', [
            $cm->id,
            $button_text
        ]);
    }
    
    return '';
    /**
 * Serves the plugin's backup/restore functionality
 *
 * @return array
 */
function local_aigrade_backup_callback() {
    return [
        'backup' => [
            'mod/assign' => 'backup/moodle2/backup_local_aigrade_plugin.class.php',
        ],
        'restore' => [
            'mod/assign' => 'backup/moodle2/restore_local_aigrade_plugin.class.php',
        ],
    ];
}
}
