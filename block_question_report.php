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
use block_question_report\output\overview;
use block_question_report\quiz_helper;

/**
 * Question report block.
 *
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */
class block_question_report extends block_base {

    /**
     * @return true
     */
    public function has_config(): bool {
        return true;
    }

    public function init(): void {
        $this->title = get_string('pluginname', 'block_question_report');
    }

    public function applicable_formats(): array {
        return ['course' => true];
    }

    public function instance_allow_multiple(): bool {
        return false;
    }

    public function get_content(): object {
        global $COURSE;
        if ($this->content !== null) {
            return $this->content;
        }
        $renderer = $this->page->get_renderer('block_question_report');
        $helper = new quiz_helper($COURSE->id);
        $overview = new overview();
        $overview->set_quiz_list($helper->get_quiz_list());
        $overview->set_instanceid($this->instance->id);
        $this->content = new stdClass();
        $this->content->text = $renderer->render_overview($overview);
        $this->content->footer = '';
        return $this->content;
    }

    /**
     * Serialize and store config data
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone($data);
        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->text = file_save_draft_area_files($data->text['itemid'],
            $this->context->id,
            'block_question_report',
            'content',
            0,
            ['subdirs' => true],
            $data->text['text']);
        $config->format = $data->text['format'];

        parent::instance_config_save($config, $nolongerused);
    }

    /**
     * @return true
     */
    public function instance_delete(): bool {
        $fs = get_file_storage();
        $fs->delete_area_files($this->context->id, 'block_question_report');
        return true;
    }

    /**
     * Copy any block-specific data when copying to a new block instance.
     *
     * @param int $fromid the id number of the block instance to copy from
     * @return boolean
     * @throws coding_exception
     */
    public function instance_copy($fromid): bool {
        $fromcontext = context_block::instance($fromid);
        $fs = get_file_storage();
        // This extra check if file area is empty adds one query if it is not empty but saves several if it is.
        if (!$fs->is_area_empty($fromcontext->id, 'block_question_report', 'content', 0, false)) {
            $draftitemid = 0;
            file_prepare_draft_area($draftitemid,
                $fromcontext->id,
                'block_question_report',
                'content',
                0,
                ['subdirs' => true]);
            file_save_draft_area_files($draftitemid,
                $this->context->id,
                'block_question_report',
                'content',
                0,
                ['subdirs' => true]);
        }
        return true;
    }

    /**
     * @return bool
     * @throws coding_exception
     */
    public function content_is_trusted(): bool {
        global $SCRIPT;

        if (!$context = context::instance_by_id($this->instance->parentcontextid, IGNORE_MISSING)) {
            return false;
        }
        // Find out if this block is on the profile page.
        if ($context->contextlevel == CONTEXT_USER) {
            if ($SCRIPT === '/my/index.php') {
                // This is exception - page is completely private, nobody else may see content there,
                // that is why we allow JS here.
                return true;
            } else {
                // No JS on public personal pages, it would be a big security issue.
                return false;
            }
        }

        return true;
    }
}

