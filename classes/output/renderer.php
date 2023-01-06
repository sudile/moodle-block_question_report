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
 * @package    report_matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report\output;

use moodle_exception;

/**
 * Renderer for Matrixreport report
 *
 * @package    report_matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render quiz instance overview
     *
     * @param overview $overview
     * @return string|boolean
     * @throws moodle_exception
     */
    public function render_overview(overview $overview) {
        $data = $overview->export_for_template($this);
        return $this->render_from_template('block_question_report/overview', $data);
    }

    /**
     * Render quiz attempt
     *
     * @param attempt $attempt
     * @return string|boolean
     * @throws moodle_exception
     */
    public function render_attempt(attempt $attempt) {
        $data = $attempt->export_for_template($this);
        $data['chart'] = $this->render($data['chart']);
        return $this->render_from_template('block_question_report/attempt', $data);
    }
}
