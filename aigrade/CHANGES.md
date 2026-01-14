# Changelog

All notable changes to the AI Grade plugin will be documented in this file.

## [1.0.0] - 2025-01-12

### Added
- Initial release
- AI-powered assignment grading using Moodle core AI subsystem
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
- Support for online text submissions
- Encourages constructive, grade-appropriate feedback
- Ohio public school context awareness
- File-based rubric upload and text extraction
- Real-time button display on grading pages

### Added
- Initial release
- AI-powered assignment grading using Moodle core AI subsystem
- Support for multiple submission types:
  - Online text submissions
  - File uploads: PDF, DOCX, DOC, TXT, PPTX, PPT, ODT
  - Google Docs links (via export API)
  - Google Slides links (via export API)

### Technical
- Moodle 4.5+ compatibility
- PHP 7.4+ compatibility
- Database table: local_aigrade_config
- Proper upgrade path handling
- Language string externalization
- Coding standards compliance
