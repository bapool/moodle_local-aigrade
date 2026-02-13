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
 * @copyright  2025 Brian A. Pool, National Trail Local Schools
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
        $this->context = $context;
        $this->aiconfig = $aiconfig;
    }
    
    /**
     * Grade all ungraded submissions using AI
     *
     * @return array Result with success status, count, and any errors
     */
    public function grade_submissions() {
        global $USER;
        
        try {
            // Get the rubric PDF or assignment description
            $rubric_text = $this->get_rubric_text();
            if (!$rubric_text) {
                // No rubric, use assignment description instead
                $rubric_text = $this->get_assignment_description();
            }
            
            // Determine which instructions to use based on whether we have a rubric
            $has_rubric_text = $this->get_rubric_text();
            
            if ($has_rubric_text) {
                // Use WITH rubric instructions
                $instructions = $this->aiconfig->instructions_with_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_with_rubric');
                }
            } else {
                // Use WITHOUT rubric instructions
                $instructions = $this->aiconfig->instructions_without_rubric ?? '';
                if (empty($instructions)) {
                    $instructions = get_config('local_aigrade', 'default_instructions_without_rubric');
                }
            }
            
            // Get ungraded submissions
            $ungraded = $this->get_ungraded_submissions();
            
            if (empty($ungraded)) {
                return ['success' => false, 'error' => get_string('no_ungraded', 'local_aigrade')];
            }
            
            $count = 0;
            foreach ($ungraded as $userid => $submission) {
                // Get submission text
                $submission_text = $this->get_submission_text($userid);
                
                if (empty($submission_text)) {
                    continue;
                }
                
                // Get max grade
                $max_grade = $this->assignment->get_instance()->grade;
                if ($max_grade < 0) {
                    $max_grade = 100;
                }
                
                // Build the AI prompt
                $prompt = $this->build_prompt($instructions, $rubric_text, $submission_text, $max_grade);
                
                // Call AI service
                $feedback = $this->call_ai_service($prompt);
                
                if ($feedback) {
                    // Save the grade and feedback
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
     * @return array Result with success status and any errors
     */
    public function grade_single_submission($userid, $force_regrade = false) {
        global $USER;
        
        try {
            // Check if already graded (unless force_regrade is true)
            if (!$force_regrade) {
                $grade = $this->assignment->get_user_grade($userid, false);
                
                // If graded (has a grade that's not -1, null, or empty)
                if ($grade && $grade->grade != -1 && $grade->grade !== null && $grade->grade !== '') {
                    return [
                        'success' => false, 
                        'already_graded' => true,
                        'error' => 'This submission is already graded. Click again to regrade.'
                    ];
                }
            }
            
            // Get the rubric PDF or assignment description
            $rubric_text = $this->get_rubric_text();
            if (!$rubric_text) {
                $rubric_text = $this->get_assignment_description();
            }
            
            // Determine which instructions to use
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
            
            // Get submission text
            $submission_text = $this->get_submission_text($userid);
            
            if (empty($submission_text)) {
                return ['success' => false, 'error' => get_string('error_no_submission', 'local_aigrade')];
            }
            
            // Get max grade
            $max_grade = $this->assignment->get_instance()->grade;
            if ($max_grade < 0) {
                $max_grade = 100;
            }
            
            // Build the AI prompt
            $prompt = $this->build_prompt($instructions, $rubric_text, $submission_text, $max_grade);
            
            // Call AI service
            $feedback = $this->call_ai_service($prompt);
            
            if ($feedback) {
                // Save the grade and feedback
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
        $fs = get_file_storage();
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
        global $CFG;
        
        // Use Moodle's temp directory
        $tempdir = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.pdf';
        $file->copy_content_to($tempfile);
        
        try {
            // Use pdftotext command if available
            $output = [];
            $return_var = 0;
            exec("pdftotext " . escapeshellarg($tempfile) . " -", $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                $text = implode("\n", $output);
            } else {
                // Fallback: basic PDF text extraction
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
        global $CFG;
        
        // Use Moodle's temp directory
        $tempdir = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.docx';
        $file->copy_content_to($tempfile);
        
        try {
            // Try using docx2txt if available
            $output = [];
            $return_var = 0;
            exec("docx2txt " . escapeshellarg($tempfile) . " -", $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                $text = implode("\n", $output);
                @unlink($tempfile);
                return $text;
            }
            
            // Fallback: Try to extract using PHP ZipArchive (docx is a zip file)
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $xml = $zip->getFromName('word/document.xml');
                    $zip->close();
                    
                    if ($xml) {
                        // Strip XML tags to get plain text
                        $text = strip_tags($xml);
                        // Clean up whitespace
                        $text = preg_replace('/\s+/', ' ', $text);
                        $text = trim($text);
                        @unlink($tempfile);
                        return $text;
                    }
                }
            }
            
            // If all else fails, return filename
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
        $instance = $this->assignment->get_instance();
        
        // Get the intro text (assignment description)
        $description = '';
        
        if (!empty($instance->intro)) {
            // Strip HTML tags but keep basic structure
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
        $groupid = optional_param('group', 0, PARAM_INT);
        $submissions = $this->assignment->list_participants($groupid, false);
        $ungraded = [];
        
        foreach ($submissions as $userid => $participant) {
            $submission = $this->assignment->get_user_submission($userid, false);
            
            // Check if submission exists and is submitted
            if (!$submission || $submission->status !== ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                continue;
            }
            
            // Get the grade
            $grade = $this->assignment->get_user_grade($userid, false);
            
            // Consider ungraded if:
            // 1. No grade record exists, OR
            // 2. Grade is -1 (Moodle's "no grade" value), OR
            // 3. Grade is null/empty
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
        
        // 1. Check for online text submission
        $onlinetext = $DB->get_record('assignsubmission_onlinetext', 
            ['assignment' => $this->assignment->get_instance()->id, 'submission' => $submission->id]);
        
        if ($onlinetext && !empty($onlinetext->onlinetext)) {
            $text = strip_tags($onlinetext->onlinetext);
            
            // Check if text contains Google Docs links
            $google_text = $this->extract_google_docs_text($text);
            if ($google_text) {
                $text .= "\n\n" . $google_text;
            }
        }
        
        // 2. Check for file submissions
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
        $extracted = '';
        
        // Pattern for Google Docs only (Slides are not supported due to visual content limitations)
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
            
            $content = curl_exec($ch);
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
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'assignsubmission_file', 
            'submission_files', $submission->id, 'filename', false);
        
        $text = '';
        
        foreach ($files as $file) {
            $filename = $file->get_filename();
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
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
                    // Unsupported file type
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
        global $CFG;
        
        // Use Moodle's temp directory
        $tempdir = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.pptx';
        $file->copy_content_to($tempfile);
        
        try {
            // PPTX is a zip file, extract slide text from XML
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $text = '';
                    $slide_count = 0;
                    
                    // Loop through slides
                    for ($i = 1; $i <= 50; $i++) { // Max 50 slides
                        $slide_xml = $zip->getFromName("ppt/slides/slide{$i}.xml");
                        if ($slide_xml === false) {
                            break;
                        }
                        
                        $slide_count++;
                        // Extract text from XML
                        $slide_text = strip_tags($slide_xml);
                        $slide_text = preg_replace('/\s+/', ' ', $slide_text);
                        $text .= "\n\nSlide {$slide_count}:\n" . trim($slide_text);
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
     * Extract text from an ODT (OpenDocument Text) file
     *
     * @param \stored_file $file
     * @return string The extracted text
     */
    private function extract_odt_text($file) {
        global $CFG;
        
        // Use Moodle's temp directory
        $tempdir = make_request_directory();
        $tempfile = $tempdir . '/aigrade_' . uniqid() . '.odt';
        $file->copy_content_to($tempfile);
        
        try {
            // ODT is a zip file with content.xml
            if (class_exists('ZipArchive')) {
                $zip = new \ZipArchive();
                if ($zip->open($tempfile) === true) {
                    $xml = $zip->getFromName('content.xml');
                    $zip->close();
                    
                    if ($xml) {
                        // Strip XML tags to get plain text
                        $text = strip_tags($xml);
                        // Clean up whitespace
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
        // REPLACE lines 635-698 in /var/www/html/local/aigrade/classes/grader.php with this:

        $has_rubric = (strpos($rubric_text, $assignment_label) !== 0);
        
        $grade_level = $this->aiconfig->grade_level ?? '9';
        
        // Map grade level to Lexile level and reading complexity
        $lexile_map = [
            '3' => ['lexile' => '420L', 'vocab' => 'simple, concrete words', 'sentence' => 'short sentences (8-12 words)', 'strictness' => 'very generous - reward any reasonable effort'],
            '4' => ['lexile' => '650L', 'vocab' => 'basic vocabulary', 'sentence' => 'simple sentences (10-14 words)', 'strictness' => 'very generous - focus on effort and participation'],
            '5' => ['lexile' => '830L', 'vocab' => 'grade-appropriate vocabulary', 'sentence' => 'clear, straightforward sentences', 'strictness' => 'generous - emphasize growth and trying'],
            '6' => ['lexile' => '925L', 'vocab' => 'moderate vocabulary', 'sentence' => 'varied sentence structure', 'strictness' => 'encouraging - reward solid attempts'],
            '7' => ['lexile' => '970L', 'vocab' => 'intermediate vocabulary', 'sentence' => 'moderately complex sentences', 'strictness' => 'balanced - recognize good work while noting areas for improvement'],
            '8' => ['lexile' => '1010L', 'vocab' => 'appropriate academic vocabulary', 'sentence' => 'varied complexity', 'strictness' => 'balanced - expect competent work but allow for learning'],
            '9' => ['lexile' => '1050L', 'vocab' => 'high school level vocabulary', 'sentence' => 'complex sentence structures', 'strictness' => 'fair - expect quality work with room for development'],
            '10' => ['lexile' => '1080L', 'vocab' => 'advanced vocabulary', 'sentence' => 'sophisticated sentences', 'strictness' => 'appropriately rigorous - expect well-developed work'],
            '11' => ['lexile' => '1185L', 'vocab' => 'college-prep vocabulary', 'sentence' => 'complex, nuanced sentences', 'strictness' => 'rigorous - expect college-ready work'],
            '12' => ['lexile' => '1385L', 'vocab' => 'college-level vocabulary', 'sentence' => 'sophisticated academic prose', 'strictness' => 'appropriately strict - expect college-level quality and depth']
        ];
        
        $lexile_info = $lexile_map[$grade_level] ?? $lexile_map['9'];
        $lexile_level = $lexile_info['lexile'];
        $vocab_level = $lexile_info['vocab'];
        $sentence_level = $lexile_info['sentence'];
        $strictness = $lexile_info['strictness'];
        
        // Map grade level to description
        $grade_desc = '';
        if ($grade_level <= 5) {
            $grade_desc = 'elementary school';
        } else if ($grade_level <= 8) {
            $grade_desc = 'middle school';
        } else {
            $grade_desc = 'high school';
        }
        
        if ($has_rubric) {
            // Prompt for grading WITH a rubric
            $prompt = "You are grading a Grade {$grade_level} {$grade_desc} student's assignment (Lexile {$lexile_level}) in an Ohio public school.\n\n";
            $prompt .= "GRADING PHILOSOPHY FOR GRADE {$grade_level}: Be {$strictness}. Students at this grade level should be held to grade-appropriate standards.\n\n";
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
            $prompt .= "CRITICAL FEEDBACK REQUIREMENTS FOR GRADE {$grade_level} (LEXILE {$lexile_level}):\n";
            $prompt .= "- GRADING STRICTNESS: {$strictness}\n";
            $prompt .= "- Write ALL feedback at exactly Lexile {$lexile_level} reading level\n";
            $prompt .= "- Use {$vocab_level} in your feedback\n";
            $prompt .= "- Use {$sentence_level} in your feedback\n";
            $prompt .= "- Apply grade-appropriate expectations - Grade {$grade_level} work should meet Grade {$grade_level} standards";
        } else {
            // Prompt for grading WITHOUT a rubric (using assignment description)
            $prompt = "You are grading a Grade {$grade_level} {$grade_desc} student's assignment (Lexile {$lexile_level}) in an Ohio public school.\n\n";
            $prompt .= "GRADING PHILOSOPHY FOR GRADE {$grade_level}: Be {$strictness}. Students at this grade level should be held to grade-appropriate standards.\n\n";
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
            $prompt .= "CRITICAL FEEDBACK REQUIREMENTS FOR GRADE {$grade_level} (LEXILE {$lexile_level}):\n";
            $prompt .= "- GRADING STRICTNESS: {$strictness}\n";
            $prompt .= "- Write ALL feedback at exactly Lexile {$lexile_level} reading level\n";
            $prompt .= "- Use {$vocab_level} in your feedback\n";
            $prompt .= "- Use {$sentence_level} in your feedback\n";
            $prompt .= "- Apply grade-appropriate expectations - Grade {$grade_level} work should meet Grade {$grade_level} standards";
        }
        
        // TEMPORARY DEBUG - Remove after testing
        error_log('AI Grade Debug - Grade Level: ' . $grade_level);
        error_log('AI Grade Debug - Lexile Level: ' . $lexile_level);
        error_log('AI Grade Debug - Strictness: ' . $strictness);
        
        return $prompt;
    }
    
    /**
     * Call the AI service to get feedback
     *
     * @param string $prompt
     * @return string|false The AI feedback or false on error
     */
    private function call_ai_service($prompt) {
        global $USER;
        
        try {
            // Create AI action using Moodle's core AI system
            $action = new \core_ai\aiactions\generate_text(
                contextid: $this->context->id,
                userid: $USER->id,
                prompttext: $prompt
            );
            
            // Get AI manager and process the action
            $manager = \core\di::get(\core_ai\manager::class);
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
        
        // Get max grade for validation
        $max_grade = $this->assignment->get_instance()->grade;
        if ($max_grade < 0) {
            $max_grade = 100; // Scale-based grading
        }
        
        // Try to extract numeric grade from feedback
        $numeric_grade = -1; // Default to ungraded
        
        // Look for "GRADE: XX" pattern
        if (preg_match('/GRADE:\s*(\d+(?:\.\d+)?)/i', $feedback, $matches)) {
            $numeric_grade = floatval($matches[1]);
            
            // Validate grade is within range
            if ($numeric_grade > $max_grade) {
                $numeric_grade = $max_grade; // Cap at maximum
            }
            if ($numeric_grade < 0) {
                $numeric_grade = 0; // Floor at 0
            }
            
            // Remove the grade line from feedback
            $feedback = preg_replace('/GRADE:\s*\d+(?:\.\d+)?\s*\n*/i', '', $feedback);
        }
        
        // Clean up the feedback - remove all label prefixes and numbering
        $feedback = preg_replace('/FEEDBACK:\s*/i', '', $feedback);
        $feedback = preg_replace('/^POSITIVE:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^IMPROVEMENTS?:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*FEEDBACK:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*POSITIVE:\s*/mi', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s*IMPROVEMENTS?:\s*/mi', '', $feedback);
        
        // Remove numbered list formatting (1. /15 2. etc.)
        $feedback = preg_replace('/^\d+\.\s*\/\d+\s*/m', '', $feedback);
        $feedback = preg_replace('/^\d+\.\s+/m', '', $feedback);
        
        // Keep RUBRIC BREAKDOWN header clean
        $feedback = preg_replace('/RUBRIC BREAKDOWN:/mi', 'RUBRIC BREAKDOWN:', $feedback);
        
        $feedback = trim($feedback);
        
        // Create grade object
        $grade = $this->assignment->get_user_grade($userid, true);
        
        // Set the numeric grade
        $grade->grade = $numeric_grade;
        $grade->grader = $USER->id;
        
        // Save grade
        $this->assignment->update_grade($grade);
        
        // Add feedback comment
        $plugin = $this->assignment->get_feedback_plugin_by_type('comments');
        if ($plugin) {
            $grade_data = new \stdClass();
            $grade_data->assignfeedbackcomments_editor = [
                'text' => $feedback,
                'format' => FORMAT_HTML,
            ];
            
            $plugin->save($grade, $grade_data);
        }
        
        return true;
    }
}
