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
 * Question report block.
 *
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */
class block_question_report extends block_base {

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
        $this->content = new stdClass();
        $this->content->text = html_writer::link(new moodle_url('/blocks/question_report/index.php',
            ['id' => $COURSE->id]),
            get_string('reports', 'block_question_report'), ['class' => 'btn btn-primary']);
        $this->content->footer = '';
        return $this->content;
    }
}

