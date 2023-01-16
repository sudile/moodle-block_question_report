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
 * Form for editing question_report block instances.
 *
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */
class block_question_report_edit_form extends block_edit_form {
    /**
     * Define block instance settings
     *
     * @param \MoodleQuickForm $mform
     * @return void
     * @throws coding_exception
     */
    protected function specific_definition($mform): void {
        // Fields for editing question_report block title and contents.
        $mform->addElement('header', 'configheader', new lang_string('blocksettings', 'block'));

        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context];
        $mform->addElement('editor',
            'config_text',
            new lang_string('config_content', 'block_question_report'),
            null,
            $editoroptions);
        $mform->addRule('config_text', null, 'required', null, 'client');
        $mform->setType('config_text',
            PARAM_RAW); // XSS is prevented when printing the block contents and serving files

        $mform->addElement('select',
            'config_data_visibility',
            new lang_string('config_data_visibility', 'block_question_report'),
            [
                1 => new lang_string('data_visibility_all', 'block_question_report'),
                2 => new lang_string('data_visibility_diagram', 'block_question_report'),
                3 => new lang_string('data_visibility_table', 'block_question_report'),
            ]);
    }

    /**
     * Parse data
     *
     * @param stdClass $defaults
     * @return void
     */
    function set_data($defaults): void {
        if (!empty($this->block->config) && !empty($this->block->config->text)) {
            $text = $this->block->config->text;
            $draftid_editor = file_get_submitted_draft_itemid('config_text');
            if (empty($text)) {
                $currenttext = '';
            } else {
                $currenttext = $text;
            }
            $defaults->config_text['text'] = file_prepare_draft_area($draftid_editor,
                $this->block->context->id,
                'block_question_report',
                'content',
                0,
                ['subdirs' => true],
                $currenttext);
            $defaults->config_text['itemid'] = $draftid_editor;
            $defaults->config_text['format'] = $this->block->config->format ?? FORMAT_MOODLE;
        } else {
            $text = '';
        }

        if (!$this->block->user_can_edit() && !empty($this->block->config->data_visibility)) {
            // If a title has been set but the user cannot edit it format it nicely
            $visibility = $this->block->config->data_visibility;
            if ($visibility <= 0 || $visibility >= 3) {
                $visibility = 1;
            }
            $defaults->config_data_visibility = $visibility;
            // Remove the title from the config so that parent::set_data doesn't set it.
            unset($this->block->config->data_visibility);
        }

        // have to delete text here, otherwise parent::set_data will empty content
        // of editor
        unset($this->block->config->text);
        parent::set_data($defaults);
        // restore $text
        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }
        $this->block->config->text = $text;
        if (isset($visibility)) {
            // Reset the preserved visibility
            $this->block->config->title = $visibility;
        }
    }
}
