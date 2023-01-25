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
 * Report renderer
 *
 * @package    block_question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report\output;

use moodle_exception;
use moodle_url;

/**
 * Renderer for Matrixreport report
 *
 * @package    block_question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render quiz instance overview
     *
     * @param overview $overview
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_overview(overview $overview) : string {
        $data = $overview->export_for_template($this);
        return $this->render_from_template('block_question_report/overview', $data);
    }

    /**
     * Render quiz attempt
     *
     * @param attempt $attempt
     * @throws moodle_exception
     */
    public function render_attempt(attempt $attempt) {
        $this->page->requires->css('/blocks/question_report/styles/attempt.css');
        $this->page->requires->js_call_amd('block_question_report/canvastoimage', 'init', []);
        echo $this->output->header();
        $data = $attempt->export_for_template($this);
        if (isset($data['chart'] )) {
            $data['chart'] = $this->render($data['chart']);
        }
        echo $this->render_from_template('block_question_report/attempt', $data);
        echo $this->output->footer();
    }

    /**
     * Render quiz empty page
     *
     * @throws moodle_exception
     */
    public function render_noattempt(string $name, int $courseid) {
        echo $this->output->header();
        echo $this->render_from_template('block_question_report/noattempt',
            [
                'quizname' => $name,
                'back' => new moodle_url('/course/view.php', ['id' => $courseid])
            ]);
        echo $this->output->footer();
    }
}
