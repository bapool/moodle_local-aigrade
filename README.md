# AI Grade - Moodle Local Plugin

AI-powered assignment grading assistant for Moodle that uses AI to provide intelligent, constructive feedback on student submissions.

## Features

- **Automated Grading**: Uses Moodle's core AI subsystem to grade student assignments
- **Multiple Submission Types**:
  - Online text submissions
  - File uploads: PDF, DOCX, DOC, TXT, PPTX, PPT, ODT
  - Google Docs links (text content only - when shared with "Anyone with the link")
- **Rubric Support**: Upload PDF, DOCX, DOC, or TXT rubric files for structured grading
- **Dual Prompt System**: Separate AI instructions for assignments with and without rubrics
- **Grade Level Awareness**: Adjusts feedback complexity and tone based on student grade level (3-12)
- **Customizable AI Name**: Personalize the grading assistant's name (e.g., "Boone", "Professor AI")
- **Flexible Grading**: Grade individual submissions or bulk grade all ungraded submissions
- **Teacher Control**: Teachers can review and modify all AI-generated grades and feedback

## Requirements

- Moodle 4.5 or later
- PHP 7.4 or later
- Moodle AI subsystem configured with an AI provider (e.g., OpenAI, Claude)
- For PDF rubrics: pdftotext command-line tool (optional but recommended)
- PHP cURL extension (for Google Docs/Slides support)
- PHP ZipArchive extension (for DOCX, PPTX, ODT support)

## Installation

### Method 1: Download and Install

1. Download the plugin zip file
2. Log in to your Moodle site as an administrator
3. Navigate to **Site administration → Plugins → Install plugins**
4. Upload the zip file
5. Click "Install plugin from the ZIP file"
6. Follow the on-screen instructions

### Method 2: Manual Installation

1. Extract the plugin files
2. Copy the `aigrade` folder to `/path/to/moodle/local/`
3. Visit **Site administration → Notifications** to complete the installation

## Configuration

### Site-Wide Settings

1. Navigate to **Site administration → Plugins → Local plugins → AI Grade**
2. Configure the following settings:
   - **AI Assistant Name**: Customize the name (default: "AI")
   - **Default AI Instructions (With Rubric)**: Default grading instructions when a rubric is uploaded
   - **Default AI Instructions (Without Rubric)**: Default grading instructions when no rubric exists

### Configure Moodle AI Subsystem

Ensure you have configured the Moodle AI subsystem:
1. Go to **Site administration → AI → AI Providers**
2. Add and configure an AI provider (e.g., OpenAI, Azure OpenAI, or Anthropic Claude)
3. Enable the provider

## Usage

### Enabling AI Grade for an Assignment

1. Create or edit an assignment
2. Scroll to the **AI Grade** section
3. Check **Enable AI Grade**
4. Select the appropriate **Student Grade Level** (3-12)
5. (Optional) Customize the AI grading instructions for this assignment
6. (Optional) Upload a grading rubric (PDF, DOCX, DOC, or TXT)
7. Save the assignment

### Grading Individual Submissions

1. Navigate to an assignment with AI Grade enabled
2. Click **View all submissions**
3. Click on a student's submission
4. Click the **[AI Name] Grade** button (e.g., "Boone Grade")
5. The AI will grade the submission and populate the feedback field
6. Review and modify the grade/feedback as needed
7. Click **Save changes**

### Bulk Grading

1. Navigate to an assignment with AI Grade enabled
2. Click **View all submissions**
3. Click the **[AI Name] Grade All** button
4. The AI will grade all ungraded submissions
5. Review and modify grades as needed

## How It Works

1. **Submission Analysis**: AI reads the student's submitted text
2. **Rubric/Criteria Review**: AI considers uploaded rubric or assignment description
3. **Grade Level Adjustment**: AI adjusts language and expectations based on grade level
4. **Feedback Generation**: AI generates constructive feedback with specific suggestions
5. **Teacher Review**: Teachers can modify grades and feedback before finalizing

## Privacy

This plugin complies with GDPR and includes:
- Privacy API implementation
- No personal data storage beyond standard Moodle assignment grading
- Teacher control over all AI-generated content

## Limitations

- **Text-based evaluation only**: AI grading evaluates text content and cannot assess:
  - Images, graphics, or visual elements
  - Formatting, colors, or design
  - Tables or charts (content may be extracted but structure is lost)
  - For presentations: slide design, animations, or visual layout
- **Best suited for**: Essays, written responses, research papers, and text-heavy assignments
- **Not recommended for**: Design projects, visual presentations, or assignments where images/formatting are being graded
- **Google Docs**: Only text content is extracted; images and formatting are not included
- **Presentations (PPTX/PPT)**: Text content and slide count are evaluated, but not visual design

## Support

- **Documentation**: [Plugin page on Moodle.org](https://moodle.org/plugins/local_aigrade)
- **Issue Tracker**: Report bugs or feature requests via the Moodle plugins directory
- **Community**: Discussion forums on Moodle.org

## Credits

**Author**: Brian A. Pool, National Trail Local Schools  
**Copyright**: 2025 Brian A. Pool, National Trail Local Schools  
**License**: GNU GPL v3 or later

## License

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
