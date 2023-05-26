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
 * @package    block
 * @subpackage question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

/**
 * Specialised backup task for the question_report block
 * (requires encode_content_links in some configdata attrs)
 */
class backup_question_report_block_task extends backup_block_task {

    protected function define_my_settings(): void {
    }

    protected function define_my_steps(): void {
    }

    public function get_fileareas(): array {
        return ['content'];
    }

    public function get_configdata_encoded_attributes(): array {
        return ['text']; // We need to encode some attrs in configdata.
    }

    public static function encode_content_links($content) {
        return $content; // No special encoding of links.
    }
}

