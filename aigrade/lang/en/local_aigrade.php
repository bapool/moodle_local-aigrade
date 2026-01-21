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
 * English language strings for local_aigrade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Grade';
$string['aigrade'] = 'AI Grade';
$string['aigrade_button'] = 'AI Grade Submissions';
$string['aigrade_single'] = 'AI Grade This Submission';
$string['aigrade_instructions'] = 'AI Grading Instructions';
$string['aigrade_instructions_desc'] = 'Default instructions for the AI when grading assignments';
$string['no_rubric'] = 'No PDF rubric found. Please attach a PDF rubric to the assignment.';
$string['no_ungraded'] = 'No ungraded submissions found.';
$string['grading_success'] = 'Successfully graded {$a} submission(s).';
$string['grading_error'] = 'Error grading submission: {$a}';
$string['ai_error'] = 'AI service error: {$a}';
$string['rubric_parse_error'] = 'Error parsing rubric PDF: {$a}';
$string['processing'] = 'Processing...';
$string['confirm_grade'] = 'Grade {$a} ungraded submission(s) with AI?';
$string['privacy:metadata:ai_provider'] = 'Student submission content is sent to the configured AI provider for grading analysis. This data is processed by the external AI service but not permanently stored by this plugin.';
$string['privacy:metadata:ai_provider:submissiontext'] = 'The text content of the student\'s submission that is sent to the AI provider for grading.';
$string['privacy:metadata:ai_provider:assignmentname'] = 'The name of the assignment, sent to provide context to the AI grading system.';
$string['privacy:metadata:ai_provider:assignmentinstructions'] = 'The assignment instructions, sent to help the AI understand grading criteria.';
$string['privacy:metadata:ai_provider:rubric'] = 'The grading rubric (if uploaded), sent to guide the AI\'s evaluation.';
$string['privacy:metadata:ai_provider:gradelevel'] = 'The student grade level (3-12), sent to help the AI adjust feedback appropriateness.';
$string['aigrade_rubric'] = 'Grading rubric';
$string['aigrade_rubric_help'] = 'Upload a rubric file (PDF, TXT, DOCX, or DOC) that the AI will use to grade student submissions.';
$string['aigrade_help'] = 'Enable AI-assisted grading for this assignment. You must upload a PDF rubric.';
$string['aigrade_instructions_help'] = 'Customize the instructions given to the AI when grading submissions. The AI will use these instructions along with the uploaded rubric.';
$string['no_rubric_warning'] = 'No rubric found. The AI will grade based on the assignment description and instructions.';
$string['aigrade_disabled'] = 'AI grading is not enabled for this assignment.';
$string['back'] = 'Back to assignment';
$string['grade_label'] = 'Grade:';
$string['feedback_label'] = 'Feedback:';
$string['instructions_label'] = 'Instructions:';
$string['grading_rubric_label'] = 'Grading rubric:';
$string['assignment_instructions_label'] = 'Assignment instructions:';
$string['student_submission_label'] = 'Student submission:';
$string['default_grading_criteria'] = 'Grade this assignment based on standard academic criteria for quality, completeness, and accuracy.';
$string['pdf_rubric_fallback'] = 'PDF rubric: {$a}';
$string['grading_with_rubric'] = 'You are grading a student assignment using a provided rubric.';
$string['grading_without_rubric'] = 'You are grading a student assignment based on the assignment requirements and general academic standards.';
$string['evaluation_criteria'] = 'Evaluate the submission on:';
$string['criteria_completeness'] = 'Completeness: Did the student address all requirements?';
$string['criteria_quality'] = 'Quality: Is the work thorough and well-executed?';
$string['criteria_accuracy'] = 'Accuracy: Is the information correct?';
$string['criteria_presentation'] = 'Presentation: Is it clear and well-organized?';
$string['aigrade_instructions_with_rubric'] = 'Default AI instructions (with rubric)';
$string['aigrade_instructions_with_rubric_desc'] = 'Default instructions used when an assignment has a PDF rubric uploaded. This text is copied into new assignments when AI Grade is enabled.';
$string['aigrade_instructions_without_rubric'] = 'Default AI instructions (without rubric)';
$string['aigrade_instructions_without_rubric_desc'] = 'Default instructions used when an assignment does NOT have a rubric. The AI will grade based on the assignment description. This text is copied into new assignments when AI Grade is enabled.';
$string['aigrade_instructions_with_rubric_field'] = 'AI instructions (when rubric uploaded)';
$string['aigrade_instructions_with_rubric_field_help'] = 'Instructions for the AI when grading WITH a rubric. The AI will use these instructions along with the uploaded rubric PDF.';
$string['aigrade_instructions_without_rubric_field'] = 'AI instructions (when no rubric)';
$string['aigrade_instructions_without_rubric_field_help'] = 'Instructions for the AI when grading WITHOUT a rubric. The AI will use these instructions along with the assignment description.';
$string['grade_level'] = 'Student grade level';
$string['grade_level_help'] = 'Select the grade level of students in this class. The AI will provide age-appropriate feedback and use vocabulary suitable for this grade level.';
$string['ai_name'] = 'AI assistant name';
$string['ai_name_desc'] = 'Customize the name of the AI grading assistant (e.g., "Boone", "Grady", "Professor AI"). This name will appear on buttons and throughout the interface. Default is "AI".';
$string['button_grade_single'] = '{$a} Grade';
$string['button_grade_bulk'] = '{$a} Grade All';
$string['google_doc_access_error'] = 'Could not access Google Doc. Please ensure the document is shared with "Anyone with the link can view".';
$string['google_slides_access_error'] = 'Could not access Google Slides. Please ensure the presentation is shared with "Anyone with the link can view".';
$string['file_extraction_error'] = 'Could not extract text from file: {$a}';
$string['unsupported_file_type'] = 'File type not supported for AI grading: {$a}';
$string['aigrade_warning'] = 'AI grading warning';
$string['aigrade_warning_text'] = '<strong>Important:</strong> Always review AI-generated grades and feedback. AI grading works best for text-based assignments and cannot evaluate images, formatting, or visual design.';
$string['error_no_submission'] = 'This student has not submitted any text for this assignment yet.';
$string['error_no_feedback'] = 'AI did not return feedback';
$string['error_invalid_action'] = 'Invalid action';

$string['grade_level_3'] = 'Grade 3 (Lexile 420L)';
$string['grade_level_4'] = 'Grade 4 (Lexile 650L)';
$string['grade_level_5'] = 'Grade 5 (Lexile 830L)';
$string['grade_level_6'] = 'Grade 6 (Lexile 925L)';
$string['grade_level_7'] = 'Grade 7 (Lexile 970L)';
$string['grade_level_8'] = 'Grade 8 (Lexile 1010L)';
$string['grade_level_9'] = 'Grade 9 (Lexile 1050L)';
$string['grade_level_10'] = 'Grade 10 (Lexile 1080L)';
$string['grade_level_11'] = 'Grade 11 (Lexile 1185L)';
$string['grade_level_12'] = 'Grade 12 (Lexile 1385L)';
$string['aigrade:grade'] = 'Use AI to grade student assignments';
$string['confirm_bulk_grade'] = 'Grade all ungraded submissions with AI? This may take a few moments.';
$string['grading_in_progress'] = 'Grading all submissions...';
$string['success_graded_count'] = 'Successfully graded {$a} submission(s).';
$string['error_with_message'] = 'Error: {$a}';
$string['error_unknown'] = 'Unknown error occurred';
$string['error_server_communication'] = 'Error communicating with server: {$a}';
$string['grading_single_in_progress'] = 'Grading...';
$string['grading_strictness'] = 'Grading strictness';
$string['grading_strictness_help'] = 'Control how strictly the AI grades submissions. "Standard" uses grade-level appropriate strictness. Override this if you want easier or harder grading regardless of grade level.';
$string['strictness_very_lenient'] = 'Very lenient - reward all reasonable efforts';
$string['strictness_lenient'] = 'Lenient - generous with points';
$string['strictness_standard'] = 'Standard for grade level (recommended)';
$string['strictness_rigorous'] = 'Rigorous - high expectations';
$string['strictness_very_rigorous'] = 'Very rigorous - college-level standards';
