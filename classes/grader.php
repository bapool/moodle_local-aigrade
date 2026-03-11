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
 * AI Grader class
 *
 * @package    local_aigrade
 * @copyright  2026 Brian A. Pool, National Trail Local Schools
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aigrade;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Class to handle AI grading of assignment submissions
 */
class grader {
    /** @var \assign The assignment object */
    private $assignment;

    /** @var \context_module The module context */
    private $context;

    /** @var \stdClass The AI config */
    private $aiconfig;

    /**
     * Constructor
     *
     * @param \assign $assignment
     * @param \context_module $context
     * @param \stdClass $aiconfig
     */
    public function __construct($assignment, $context, $aiconfig) {
        $this->assignment = $assignment;
        $this->context    = $context;
        $this->aiconfig   = $aiconfig;
    }

    /**
     * Grade all ungraded submissions using AI
     *
     * @return array Result with success status, count, and any errors
     */
    public function grade_submissions() {
        global $USER;

        try {
            $rubric_text = $this->get_rubric_text();
            if (!$rubric_text) {
                $rubric_text = $this->get_assignment_description();
            }

            $has_rubric_text = $this->get_rubric_text();

            if ($has_rubric_text) {
                $instructions = $this->aiconfig->instructions_with_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_with_rubric');
                }
            } else {
                $instructions = $this->aiconfig->instructions_without_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_without_rubric');
                }
            }

            $ungraded = $this->get_ungraded_submissions();

            if (empty($ungraded)) {
                return ['success' => false, 'error' => get_string('no_ungraded', 'local_aigrade')];
            }

            $count = 0;
            foreach ($ungraded as $userid => $submission) {
                $submission_text = $this->get_submission_text($userid);

                if (empty($submission_text)) {
                    continue;
                }

                $max_grade = $this->assignment->get_instance()->grade;
                if ($max_grade < 0) {
                    $max_grade = 100;
                }

                $prompt   = $this->build_prompt($instructions, $rubric_text, $submission_text, $max_grade);
                $feedback = $this->call_ai_service($prompt);

                if ($feedback) {
                    $this->save_grade($userid, $feedback);
                    $count++;
                }
            }

            return ['success' => true, 'count' => $count];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Grade a single submission using AI
     *
     * @param int $userid The user ID to grade
     * @param bool $force_regrade Whether to regrade already graded submissions
     * @return array Result with success status and any errors
     */
    public function grade_single_submission($userid, $force_regrade = false) {
        global $USER;

        try {
            if (!$force_regrade) {
                $grade = $this->assignment->get_user_grade($userid, false);

                if ($grade && $grade->grade != -1 && $grade->grade !== null && $grade->grade !== '') {
                    return [
                        'success'        => false,
                        'already_graded' => true,
                        'error'          => 'This submission is already graded. Click again to regrade.',
                    ];
                }
            }

            $rubric_text = $this->get_rubric_text();
            if (!$rubric_text) {
                $rubric_text = $this->get_assignment_description();
            }

            $has_rubric_text = $this->get_rubric_text();

            if ($has_rubric_text) {
                $instructions = $this->aiconfig->instructions_with_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_with_rubric');
                }
            } else {
                $instructions = $this->aiconfig->instructions_without_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_without_rubric');
                }
            }

            $submission_text = $this->get_submission_text($userid);

            if (empty($submission_text)) {
                return ['success' => false, 'error' => get_string('error_no_submission', 'local_aigrade')];
            }

            $max_grade = $this->assignment->get_instance()->grade;
            if ($max_grade < 0) {
                $max_grade = 100;
            }

            $prompt   = $this->build_prompt($instructions, $rubric_text, $submission_text, $max_grade);
            $feedback = $this->call_ai_service($prompt);

            if ($feedback) {
                $this->save_grade($userid, $feedback);
                return ['success' => true, 'regraded' => $force_regrade];
            }

            return ['success' => false, 'error' => get_string('error_no_feedback', 'local_aigrade')];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Extract text from the rubric file attachment
     *
     * @return string|false The rubric text or false if not found
     */
    private function get_rubric_text() {
        $fs    = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'local_aigrade', 'rubric',
            $this->assignment->get_instance()->id, 'filename', false);

        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION));

            switch ($extension) {
                case 'pdf':
                    return $this->extract_pdf_text($file);
                case 'txt':
                    return $this->extract_txt_text($file);
                case 'docx':
                case 'doc':
                    return $this->extract_docx_text($file);
                default:
                    break;
            }
        }

        return false;
    }

    /**
     * Extract text from a PDF file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_pdf_text($file) {
        $tempdir  = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.pdf';
        $file->copy_content_to($tempfile);

        try {
            $output     = [];
            $return_var = 0;
            exec("pdftotext " . escapeshellarg($tempfile) . " -", $output, $return_var);

            if ($return_var === 0 && !empty($output)) {
                $text = implode("\n", $output);
            } else {
                $text = get_string('pdf_rubric_fallback', 'local_aigrade', $file->get_filename());
            }

            @unlink($tempfile);
            return $text;

        } catch (\Exception $e) {
            @unlink($tempfile);
            throw new \Exception(get_string('rubric_parse_error', 'local_aigrade', $e->getMessage()));
        }
    }

    /**
     * Extract text from a TXT file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_txt_text($file) {
        return $file->get_content();
    }

    /**
     * Extract text from a DOCX file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_docx_text($file) {
        $tempdir  = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.docx';
        $file->copy_content_to($tempfile);

        try {
            $output     = [];
            $return_var = 0;
            exec("docx2txt " . escapeshellarg($tempfile) . " -", $output, $return_var);

            if ($return_var === 0 && !empty($output)) {
                $text = implode("\n", $output);
                @unlink($tempfile);
                return $text;
            }

            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();

                    if ($xml) {
                        $text = strip_tags($xml);
                        $text = preg_replace('/\s+/', ' ', $text);
                        $text = trim($text);
                        @unlink($tempfile);
                        return $text;
                    }
                }
            }

            @unlink($tempfile);
            return get_string('pdf_rubric_fallback', 'local_aigrade', $file->get_filename());

        } catch (\Exception $e) {
            @unlink($tempfile);
            throw new \Exception(get_string('rubric_parse_error', 'local_aigrade', $e->getMessage()));
        }
    }

    /**
     * Get assignment description to use as grading criteria when no rubric exists
     *
     * @return string The assignment description/instructions
     */
    private function get_assignment_description() {
        $instance    = $this->assignment->get_instance();
        $description = '';

        if (!empty($instance->intro)) {
            $description = strip_tags($instance->intro, '<p><br><ul><ol><li>');
            $description = trim($description);
        }

        if (empty($description)) {
            $description = get_string('default_grading_criteria', 'local_aigrade');
        }

        return get_string('assignment_instructions_label', 'local_aigrade') . "\n" . $description;
    }

    /**
     * Get all ungraded submissions
     *
     * @return array Array of submissions keyed by userid
     */
    private function get_ungraded_submissions() {
        $groupid     = optional_param('group', 0, PARAM_INT);
        $submissions = $this->assignment->list_participants($groupid, false);
        $ungraded    = [];

        foreach ($submissions as $userid => $participant) {
            $submission = $this->assignment->get_user_submission($userid, false);

            if (!$submission || $submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                continue;
            }

            $grade = $this->assignment->get_user_grade($userid, false);

            if (!$grade || $grade->grade == -1 || $grade->grade === null || $grade->grade === '') {
                $ungraded[$userid] = $submission;
            }
        }

        return $ungraded;
    }

    /**
     * Get submission text for a user - handles online text, file uploads, and Google Docs links
     *
     * @param int $userid
     * @return string The submission text
     */
    private function get_submission_text($userid) {
        global $DB;

        $submission = $this->assignment->get_user_submission($userid, false);
        if (!$submission) {
            return '';
        }

        $text = '';

        $onlinetext = $DB->get_record('assignsubmission_onlinetext',
            ['assignment' => $this->assignment->get_instance()->id, 'submission' => $submission->id]);

        if ($onlinetext && !empty($onlinetext->onlinetext)) {
            $text = strip_tags($onlinetext->onlinetext);

            $google_text = $this->extract_google_docs_text($text);
            if ($google_text) {
                $text .= "\n\n" . $google_text;
            }
        }

        $file_text = $this->extract_file_submission_text($submission);
        if ($file_text) {
            if (!empty($text)) {
                $text .= "\n\n--- File Submission Content ---\n\n";
            }
            $text .= $file_text;
        }

        return trim($text);
    }

    /**
     * Extract text from Google Docs links in submission text
     *
     * @param string $text The submission text that may contain Google links
     * @return string Extracted text from Google Docs
     */
    private function extract_google_docs_text($text) {
        $extracted    = '';
        $docs_pattern = '/https:\/\/docs\.google\.com\/document\/d\/([a-zA-Z0-9_-]+)/';

        if (preg_match_all($docs_pattern, $text, $matches)) {
            foreach ($matches[1] as $doc_id) {
                $content = $this->fetch_google_doc($doc_id);
                if ($content) {
                    $extracted .= "\n\n--- Google Doc Content ---\n" . $content;
                }
            }
        }

        return $extracted;
    }

    /**
     * Fetch content from a Google Doc using export API
     *
     * @param string $doc_id The Google Doc ID
     * @return string|false The document text or false on failure
     */
    private function fetch_google_doc($doc_id) {
        $export_url = "https://docs.google.com/document/d/{$doc_id}/export?format=txt";

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $export_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $content   = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code === 200 && $content) {
                return trim($content);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extract text from file submissions
     *
     * @param \stdClass $submission The submission object
     * @return string Extracted text from all submitted files
     */
    private function extract_file_submission_text($submission) {
        $fs    = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'assignsubmission_file',
            'submission_files', $submission->id, 'filename', false);

        $text = '';

        foreach ($files as $file) {
            $filename     = $file->get_filename();
            $extension    = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $file_content = '';

            switch ($extension) {
                case 'pdf':
                    $file_content = $this->extract_pdf_text($file);
                    break;
                case 'txt':
                    $file_content = $this->extract_txt_text($file);
                    break;
                case 'docx':
                case 'doc':
                    $file_content = $this->extract_docx_text($file);
                    break;
                case 'pptx':
                case 'ppt':
                    $file_content = $this->extract_pptx_text($file);
                    break;
                case 'odt':
                    $file_content = $this->extract_odt_text($file);
                    break;
                default:
                    $file_content = get_string('unsupported_file_type', 'local_aigrade', $filename);
            }

            if ($file_content) {
                if (!empty($text)) {
                    $text .= "\n\n";
                }
                $text .= "--- File: {$filename} ---\n" . $file_content;
            }
        }

        return $text;
    }

    /**
     * Extract text from a PowerPoint file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_pptx_text($file) {
        $tempdir  = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.pptx';
        $file->copy_content_to($tempfile);

        try {
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $text        = '';
                    $slide_count = 0;

                    for ($i = 1; $i <= 50; $i++) {
                        $slide_xml = $zip->getFromName("ppt/slides/slide{$i}.xml");
                        if ($slide_xml === false) {
                            break;
                        }

                        $slide_count++;
                        $slide_text = strip_tags($slide_xml);
                        $slide_text = preg_replace('/\s+/', ' ', $slide_text);
                        $text      .= "\n\nSlide {$slide_count}:\n" . trim($slide_text);
                    }

                    $zip->close();
                    @unlink($tempfile);
                    return trim($text);
                }
            }

            @unlink($tempfile);
            return "PowerPoint file: " . $file->get_filename() . " (text extraction not available)";

        } catch (\Exception $e) {
            @unlink($tempfile);
            return "PowerPoint file: " . $file->get_filename() . " (extraction error)";
        }
    }

    /**
     * Extract text from an ODT file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_odt_text($file) {
        $tempdir  = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.odt';
        $file->copy_content_to($tempfile);

        try {
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $xml = $zip->getFromName('content.xml');
                    $zip->close();

                    if ($xml) {
                        $text = strip_tags($xml);
                        $text = preg_replace('/\s+/', ' ', $text);
                        $text = trim($text);
                        @unlink($tempfile);
                        return $text;
                    }
                }
            }

            @unlink($tempfile);
            return "ODT file: " . $file->get_filename() . " (text extraction not available)";

        } catch (\Exception $e) {
            @unlink($tempfile);
            return "ODT file: " . $file->get_filename() . " (extraction error)";
        }
    }

    /**
     * Map the saved grading_strictness value to a full prompt description
     *
     * @return string Human-readable strictness instructions for the AI prompt
     */
    private function get_strictness_description() {
        $strictness_map = [
            'very_lenient'  => 'VERY LENIENT - reward all reasonable efforts. If the student made a genuine attempt, award most or all points. Partial credit should be given generously. Do not penalize for minor errors, incomplete sentences, or brief responses as long as the core idea is present.',
            'lenient'       => 'LENIENT - be generous with partial credit. Reward effort and good-faith attempts. Give the benefit of the doubt when the student shows understanding, even if the response is incomplete or has minor errors.',
            'standard'      => 'STANDARD - apply grade-level expectations fairly. Reward work that meets the requirements, give partial credit for partial understanding, and deduct points only for clear gaps in understanding or missing requirements.',
            'rigorous'      => 'RIGOROUS - hold students to high grade-level standards. Deduct points for incomplete responses, factual errors, and missing requirements. Partial credit should reflect actual demonstrated understanding.',
            'very_rigorous' => 'VERY RIGOROUS - apply strict grade-level standards. Students must fully address all requirements to earn full credit. Deduct points clearly for errors, omissions, and weak responses. Partial credit should be minimal unless understanding is clearly demonstrated.',
        ];

        $setting = $this->aiconfig->grading_strictness ?? 'standard';
        return $strictness_map[$setting] ?? $strictness_map['standard'];
    }

    /**
     * Build the AI prompt
     *
     * @param string $instructions
     * @param string $rubric_text
     * @param string $submission_text
     * @param int $max_grade
     * @return string The complete prompt
     */
    private function build_prompt($instructions, $rubric_text, $submission_text, $max_grade) {
        $assignment_label = get_string('assignment_instructions_label', 'local_aigrade');
        $has_rubric       = (strpos($rubric_text, $assignment_label) !== 0);
        $grade_level      = $this->aiconfig->grade_level ?? '9';
        $strictness_desc  = $this->get_strictness_description();

        $lexile_map = [
            '3'  => ['lexile' => '420L',  'vocab' => 'simple, concrete words',          'sentence' => 'short sentences (8-12 words)'],
            '4'  => ['lexile' => '650L',  'vocab' => 'basic vocabulary',                'sentence' => 'simple sentences (10-14 words)'],
            '5'  => ['lexile' => '830L',  'vocab' => 'grade-appropriate vocabulary',    'sentence' => 'clear, straightforward sentences'],
            '6'  => ['lexile' => '925L',  'vocab' => 'moderate vocabulary',             'sentence' => 'varied sentence structure'],
            '7'  => ['lexile' => '970L',  'vocab' => 'intermediate vocabulary',         'sentence' => 'moderately complex sentences'],
            '8'  => ['lexile' => '1010L', 'vocab' => 'appropriate academic vocabulary', 'sentence' => 'varied complexity'],
            '9'  => ['lexile' => '1050L', 'vocab' => 'high school level vocabulary',    'sentence' => 'complex sentence structures'],
            '10' => ['lexile' => '1080L', 'vocab' => 'advanced vocabulary',             'sentence' => 'sophisticated sentences'],
            '11' => ['lexile' => '1185L', 'vocab' => 'college-prep vocabulary',         'sentence' => 'complex, nuanced sentences'],
            '12' => ['lexile' => '1385L', 'vocab' => 'college-level vocabulary',        'sentence' => 'sophisticated academic prose'],
        ];

        $lexile_info    = $lexile_map[$grade_level] ?? $lexile_map['9'];
        $lexile_level   = $lexile_info['lexile'];
        $vocab_level    = $lexile_info['vocab'];
        $sentence_level = $lexile_info['sentence'];

        if ($grade_level <= 5) {
            $grade_desc = 'elementary school';
        } else if ($grade_level <= 8) {
            $grade_desc = 'middle school';
        } else {
            $grade_desc = 'high school';
        }

        if ($has_rubric) {
            $prompt  = "You are grading a Grade {$grade_level} {$grade_desc} student's assignment (Lexile {$lexile_level}) in an Ohio public school.\n\n";
            $prompt .= "GRADING STRICTNESS - THIS IS YOUR MOST IMPORTANT INSTRUCTION:\n";
            $prompt .= $strictness_desc . "\n\n";
            $prompt .= "GRADING INSTRUCTIONS:\n";
            $prompt .= $instructions . "\n\n";
            $prompt .= get_string('grading_rubric_label', 'local_aigrade') . "\n";
            $prompt .= $rubric_text . "\n\n";
            $prompt .= "Format your response EXACTLY as follows:\n\n";
            $prompt .= get_string('grade_label', 'local_aigrade') . " [numeric score out of " . $max_grade . "]\n\n";
            $prompt .= "RUBRIC BREAKDOWN:\n";
            $prompt .= "[List each rubric criterion with points earned]\n\n";
            $prompt .= "[Brief feedback addressing the student directly - do not include the word FEEDBACK]\n\n";
            $prompt .= get_string('student_submission_label', 'local_aigrade') . "\n";
            $prompt .= $submission_text . "\n\n";
            $prompt .= "FEEDBACK WRITING REQUIREMENTS:\n";
            $prompt .= "- Write ALL feedback at exactly Lexile {$lexile_level} reading level\n";
            $prompt .= "- Use {$vocab_level} in your feedback\n";
            $prompt .= "- Use {$sentence_level} in your feedback\n";
            $prompt .= "- Address the student directly using 'you'\n";
            $prompt .= "- Keep feedback concise and constructive";
        } else {
            $prompt  = "You are grading a Grade {$grade_level} {$grade_desc} student's assignment (Lexile {$lexile_level}) in an Ohio public school.\n\n";
            $prompt .= "GRADING STRICTNESS - THIS IS YOUR MOST IMPORTANT INSTRUCTION:\n";
            $prompt .= $strictness_desc . "\n\n";
            $prompt .= $rubric_text . "\n\n";
            $prompt .= "GRADING GUIDANCE:\n";
            $prompt .= $instructions . "\n\n";
            $prompt .= "Evaluate the submission on:\n";
            $prompt .= "- Completeness: Did the student address all requirements?\n";
            $prompt .= "- Quality: Is the work thorough and well-executed?\n";
            $prompt .= "- Accuracy: Is the information correct?\n";
            $prompt .= "- Presentation: Is it clear and well-organized?\n\n";
            $prompt .= "Format your response EXACTLY as follows:\n\n";
            $prompt .= get_string('grade_label', 'local_aigrade') . " [numeric score out of " . $max_grade . "]\n\n";
            $prompt .= "[Brief feedback addressing the student directly using 'you' - do not include the word FEEDBACK]\n";
            $prompt .= "   - One positive comment about what you did well\n";
            $prompt .= "   - Specific areas where points were lost\n";
            $prompt .= "   - One suggestion for improvement\n\n";
            $prompt .= get_string('student_submission_label', 'local_aigrade') . "\n";
            $prompt .= $submission_text . "\n\n";
            $prompt .= "FEEDBACK WRITING REQUIREMENTS:\n";
            $prompt .= "- Write ALL feedback at exactly Lexile {$lexile_level} reading level\n";
            $prompt .= "- Use {$vocab_level} in your feedback\n";
            $prompt .= "- Use {$sentence_level} in your feedback\n";
            $prompt .= "- Address the student directly using 'you'\n";
            $prompt .= "- Keep feedback concise and constructive";
        }

        return $prompt;
    }

    /**
     * Write the full prompt to a debug log file for inspection.
     * Files older than 2 hours are automatically cleared on each run.
     *
     * @param string $prompt The prompt being sent to the AI
     * @param int $userid The user being graded
     */
    private function debug_log_prompt($prompt, $userid) {
        $logdir = '/tmp/aigrade_debug';

        if (!is_dir($logdir)) {
            mkdir($logdir, 0755, true);
        }

        // Remove debug files older than 2 hours.
        foreach (glob($logdir . '/prompt_*.txt') as $oldfile) {
            if (filemtime($oldfile) < (time() - 7200)) {
                @unlink($oldfile);
            }
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename  = $logdir . '/prompt_user' . $userid . '_' . $timestamp . '.txt';

        $header  = "========================================\n";
        $header .= "AI Grade Debug Log\n";
        $header .= "Date/Time  : " . date('Y-m-d H:i:s') . "\n";
        $header .= "User ID    : " . $userid . "\n";
        $header .= "Assignment : " . $this->assignment->get_instance()->name . "\n";
        $header .= "Grade Lvl  : " . ($this->aiconfig->grade_level ?? 'not set') . "\n";
        $header .= "Strictness : " . ($this->aiconfig->grading_strictness ?? 'not set') . "\n";
        $header .= "========================================\n\n";
        $header .= "FULL PROMPT SENT TO AI:\n\n";

        file_put_contents($filename, $header . $prompt . "\n");
    }

    /**
     * Call the AI service to get feedback
     *
     * @param string $prompt
     * @return string|false The AI feedback or false on error
     */
    private function call_ai_service($prompt) {
        global $USER;

        // Write prompt to debug log file before sending.
        $this->debug_log_prompt($prompt, $USER->id);

        try {
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->context->id,
                userid: $USER->id,
                prompttext: $prompt
            );

            $manager  = \core\di::get(\core_ai\manager::class);
            $response = $manager->process_action($action);

            if ($response->get_success()) {
                return $response->get_response_data()['generatedcontent'] ?? '';
            } else {
                throw new \Exception($response->get_errormessage() ?: 'AI generation failed');
            }

        } catch (\Exception $e) {
            throw new \Exception(get_string('ai_error', 'local_aigrade', $e->getMessage()));
        }
    }

    /**
     * Save the grade and feedback for a user
     *
     * @param int $userid
     * @param string $feedback
     * @return bool Success status
     */
    private function save_grade($userid, $feedback) {
        global $USER;

        $max_grade = $this->assignment->get_instance()->grade;
        if ($max_grade < 0) {
            $max_grade = 100;
        }

        $numeric_grade = -1;

        if (preg_match('/GRADE:\s*(\d+(?:\.\d+)?)/i', $feedback, $matches)) {
            $numeric_grade = floatval($matches[1]);

            if ($numeric_grade > $max_grade) {
                $numeric_grade = $max_grade;
            }
            if ($numeric_grade < 0) {
                $numeric_grade = 0;
            }

            $feedback = preg_replace('/GRADE:\s*\d+(?:\.\d+)?\s*\n*/i', '', $feedback);
        }

        $feedback = preg_replace('/FEEDBACK:\s*/i', '', $feedback);
        $feedback = preg_replace('/^POSITIVE:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^IMPROVEMENTS?:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*FEEDBACK:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*POSITIVE:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*IMPROVEMENTS?:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*\/\d+\s*/m', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s+/m', '', $feedback);
        $feedback = preg_replace('/RUBRIC BREAKDOWN:/mi', 'RUBRIC BREAKDOWN:', $feedback);
        $feedback = trim($feedback);

        $grade         = $this->assignment->get_user_grade($userid, true);
        $grade->grade  = $numeric_grade;
        $grade->grader = $USER->id;

        $this->assignment->update_grade($grade);

        $plugin = $this->assignment->get_feedback_plugin_by_type('comments');
        if ($plugin) {
            $grade_data = new \stdClass();
            $grade_data->assignfeedbackcomments_editor = [
                'text'   => $feedback,
                'format' => FORMAT_HTML,
            ];

            $plugin->save($grade, $grade_data);
        }

        return true;
    }
}
