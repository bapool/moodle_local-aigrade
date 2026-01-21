# Changelog

All notable changes to the AI Grade plugin will be documented in this file.

## [1.6.0] - 2026-01-20

### Added
- **Grading Strictness Override**: Teachers can now override the default grade-level strictness
  - New dropdown in assignment settings: "Grading strictness"
  - Five levels: Very lenient, Lenient, Standard (default), Rigorous, Very rigorous
  - "Standard" uses the automatic Lexile-based strictness appropriate for the grade level
  - Teachers can make grading easier or harder regardless of grade level
  - Example: Grade 3 teacher can select "Rigorous" for advanced students
  - Example: Grade 12 teacher can select "Lenient" to be more encouraging
  - Stored in database field `grading_strictness` with proper upgrade script

### Technical
- Added `grading_strictness` field to `local_aigrade_config` table via database upgrade
- Added 7 new language strings for strictness levels and help text
- Modified grader.php to check for teacher override before applying grade-level defaults
- Database upgrade version: 2026012000

## [1.5.0] - 2026-01-20

### Added
- **Lexile Level Integration**: Each grade level now displays corresponding Lexile reading level
  - Grade 3 (Lexile 420L) through Grade 12 (Lexile 1385L)
  - AI feedback is now written at the appropriate Lexile reading level for each grade
  - Vocabulary complexity and sentence structure automatically adjusted based on Lexile level
- **Grade-Appropriate Grading Strictness**: AI applies different grading standards based on grade level
  - Elementary students (grades 3-5): Very generous grading, rewards effort and participation
  - Middle school students (grades 6-8): Balanced approach, encourages development
  - High school students (grades 9-12): Increasingly rigorous, expects college-ready work by grade 12
  - Ensures younger students receive more encouragement while older students are held to higher standards

### Changed
- Updated grade level dropdown label from "Student Grade Level" to "Student grade/Lexile level"
- Enhanced AI prompting to include explicit instructions for Lexile-appropriate feedback
- Modified grading philosophy to scale with student grade level (more generous for younger, more rigorous for older)
- AI now receives specific guidance on vocabulary complexity and sentence structure for each grade level

### Technical
- Added Lexile mapping array in grader.php for precise reading level targeting
- Added strictness parameter that varies by grade level in AI prompts
- Language strings updated to show both grade and Lexile level (e.g., "Grade 7 (Lexile 970L)")

## [1.4.0] - 2026-01-16

### Fixed
- Fixed `local_aigrade_coursemodule_standard_elements()` function signature to accept both `$formwrapper` and `$mform` parameters as required by Moodle 4.5+ hook system
- Removed duplicate header line in assignment settings form (line 64 in lib.php)
- Fixed backup and restore implementation for course backup/restore functionality
  - Simplified backup/restore to handle configuration only (rubric files must be re-uploaded after restore)
  - Resolved "unknown_context_mapping" errors during course restore
- Fixed settings.php double semicolon syntax error (line 124)
- Fixed JavaScript button loading to appear on initial page load without requiring refresh
  - Changed page type detection from `mod-assign-grading` to `mod-assign-grader`
  - Modified JavaScript to load when `action=grader` is present (even before userid parameter)
  - Implemented MutationObserver for dynamic content detection

### Changed
- Updated Privacy API implementation from `null_provider` to full metadata provider
  - Plugin now properly declares that student submission content is sent to external AI providers
  - Added detailed privacy metadata strings explaining what data is processed externally
- Updated language strings to follow Moodle capitalization guidelines (sentence case instead of Title Case)
  - Changed strings like "AI Instructions" to "AI instructions"
  - Changed "Student Grade Level" to "Student grade level"
  - Updated all label strings to use sentence case
- Improved backup/restore documentation
  - Added comments explaining that rubric files are not backed up
  - Teachers must re-upload rubric files after course restore

### Technical
- Plugin now complies with Moodle.org submission requirements
- Proper frankenstyle naming throughout
- Security checks properly implemented (require_login, require_sesskey, require_capability)
- Settings stored in config_plugins table (not config table)
- All database operations use Moodle DML API with proper parameterization
- AMD JavaScript modules properly handle dynamic page loading

## [1.3.0] - 2025-01-15

### Fixed
- Converted inline JavaScript to proper AMD modules for Moodle coding standards compliance
  - Created `amd/src/grade_single.js` for individual grading button
  - Created `amd/src/grade_bulk.js` for bulk grading button
  - Updated `lib.php` to use `js_call_amd()` instead of inline JavaScript
  - Added language string `confirm_bulk_grade` for better user experience
- Fixed duplicate headers in README.md (removed duplicate "Features" and "Support" sections)
- Created proper CSS file with namespaced selectors
  - Added `styles.css` with `.path-mod-assign` and `.local_aigrade-` prefixed classes
  - Removed inline CSS styles from JavaScript modules
  - Improved compliance with Moodle CSS guidelines
- Fixed error message display to use alert dialogs instead of top-page notifications

## [1.0.0] - 2025-01-12

### Added
- Initial release
- AI-powered assignment grading using Moodle core AI subsystem
- Support for multiple submission types:
  - Online text submissions
  - File uploads: PDF, DOCX, DOC, TXT, PPTX, PPT, ODT
  - Google Docs links (via export API)
  - Google Slides links (via export API)
- Support for rubric-based grading (PDF, DOCX, DOC, TXT formats)
- Dual prompt system (separate instructions for with/without rubric)
- Grade level awareness (grades 3-12)
- Customizable AI assistant name
- Individual and bulk grading capabilities
- Integration with Moodle assignment module
- Privacy API implementation (GDPR compliant)
- Configurable site-wide default instructions
- Per-assignment instruction customization
- Dynamic grade scaling (respects assignment point values)
- Teacher review and override capabilities
- AJAX-based grading interface
- Automatic feedback cleanup and formatting

### Features
- Seamless integration with Moodle's assignment grading interface
- Encourages constructive, grade-appropriate feedback
- File-based rubric upload and text extraction
- Real-time button display on grading pages

### Technical
- Moodle 4.5+ compatibility
- PHP 7.4+ compatibility
- Database table: local_aigrade_config
- Proper upgrade path handling
- Language string externalization
- Coding standards compliance
