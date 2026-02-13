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
 * Settings for local_aigrade
 *
 * @package    local_aigrade
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aigrade', get_string('pluginname', 'local_aigrade'));

    // Add important warning at the top
    $settings->add(new admin_setting_heading(
        'local_aigrade/warning',
        '',
        '<div class="alert alert-warning" style="margin: 20px 0; padding: 15px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 4px;">
            <h4 style="margin-top: 0; color: #856404;"><i class="fa fa-exclamation-triangle"></i> Important: AI Grading Guidelines</h4>
            <p><strong>Teachers must always review AI-generated grades and feedback for accuracy.</strong></p>
            <p><strong>Best Results:</strong> Text-based assignments such as essays, written responses, and research papers.</p>
            <p><strong>Limitations:</strong> AI grading evaluates text content only and cannot assess:</p>
            <ul>
                <li>Images, graphics, or visual elements</li>
                <li>Formatting, colors, or document design</li>
                <li>Tables, charts, or diagrams (structure may be lost)</li>
                <li>For presentations: visual design, slide layouts, or animations</li>
            </ul>
            <p><strong>Not Recommended:</strong> Assignments where images, formatting, or visual design are significant grading criteria.</p>
        </div>'
    ));
    // AI Name setting
    $settings->add(new admin_setting_configtext(
        'local_aigrade/ai_name',
        get_string('ai_name', 'local_aigrade'),
        get_string('ai_name_desc', 'local_aigrade'),
        'AI',
        PARAM_TEXT
    ));

    // Default instructions WITH rubric
    $settings->add(new admin_setting_configtextarea(
        'local_aigrade/default_instructions_with_rubric',
        get_string('aigrade_instructions_with_rubric', 'local_aigrade'),
        get_string('aigrade_instructions_with_rubric_desc', 'local_aigrade'),
        'You are grading a student assignment using the provided rubric. Grade based on text content only.

IMPORTANT GRADING PHILOSOPHY:
- Be encouraging and generous - focus on what the student did well
- Reward effort and good-faith attempts
- This is a learning process - students are still developing their skills
- Award partial credit appropriately

Provide your response in this EXACT format:

GRADE: [numeric score out of the maximum possible]

RUBRIC BREAKDOWN:
[Criterion 1]: X/Y points - [brief reason]
[Criterion 2]: X/Y points - [brief reason]
(List each rubric criterion)

[2-3 sentences of positive, encouraging feedback about what the student did well]

[1-2 specific, constructive suggestions for improvement]

GRADING REMINDERS:
- Address the student directly using "you"
- Keep feedback concise (under 100 words total)
- Focus on content, not formatting or visual elements
- Be warm, supportive, and constructive',
        PARAM_TEXT
    ));

    // Default instructions WITHOUT rubric
    $settings->add(new admin_setting_configtextarea(
        'local_aigrade/default_instructions_without_rubric',
        get_string('aigrade_instructions_without_rubric', 'local_aigrade'),
        get_string('aigrade_instructions_without_rubric_desc', 'local_aigrade'),
        'You are grading a student assignment based on the assignment requirements. Grade based on text content only.

IMPORTANT GRADING PHILOSOPHY:
- Be encouraging and generous
- Give credit for effort and thoughtful attempts
- Focus on learning and growth, not perfection
- Award points for reasonable efforts

Evaluate the submission on:
- Completeness: Did the student address all requirements?
- Quality: Is the work thorough and demonstrates understanding?
- Accuracy: Is the information correct?
- Clarity: Is it well-organized and clearly communicated?

Provide your response in this EXACT format:

GRADE: [numeric score out of the maximum possible]

[2-3 sentences about what the student did well]

[1-2 specific areas for improvement with constructive suggestions]

GRADING REMINDERS:
- Address the student directly using "you"
- Keep feedback concise (under 100 words total)
- Focus on content, not formatting or visual elements
- Be supportive and warm',
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
}
