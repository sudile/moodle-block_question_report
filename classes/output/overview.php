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
 * Contains class overview
 *
 * @package    block_question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report\output;


use renderable;
use renderer_base;
use templatable;

class overview implements renderable, templatable {
    /**
     * @var \cm_info[]
     */
    private $quizlist;

    private $instanceid;

    /**
     * @param \cm_info[] $quizlist
     * @return void
     */
    public function set_quiz_list(array $quizlist) {
        $this->quizlist = $quizlist;
    }

    /**
     * @param int $instanceid
     * @return void
     */
    public function set_instanceid(int $instanceid) {
        $this->instanceid = $instanceid;
    }

    public function export_for_template(renderer_base $output): array {
        $data = [
            'quizzes' => [],
            'candownload' => has_capability('block/question_report:download', \context_block::instance($this->instanceid)),
        ];
        foreach ($this->quizlist as $quiz) {
            $data['quizzes'][] = [
                'name' => $quiz->get_name(),
                'url' => new \moodle_url('/blocks/question_report/index.php',
                    ['id' => $this->instanceid, 'cmid' => $quiz->id]),
                'description' => $quiz->content,
                'dowloadurl' => new \moodle_url('/blocks/question_report/index.php',
                    ['id' => $this->instanceid, 'cmid' => $quiz->id, 'download' => '1']),
            ];
        }
        return $data;
    }
}
