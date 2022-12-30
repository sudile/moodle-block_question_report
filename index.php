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
 * Display user activity reports for a course (totals)
 *
 * @package    report
 * @subpackage matrixreport
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$course = get_course($id);

$PAGE->set_url(new moodle_url('/report/matrixreport/index.php'));
$PAGE->set_pagelayout('report');

require_login($course);
$context = context_course::instance($course->id);

require_capability('report/matrixreport:view', $context);

echo $OUTPUT->header();
if ($cmid === 0) {
    $helper = new \report_matrixreport\quiz_helper($course->id);
    $overview = new \report_matrixreport\output\overview();
    $overview->set_quiz_list($helper->get_quiz_list());
    $renderer = $PAGE->get_renderer('report_matrixreport');
    echo $renderer->render_overview($overview);
} else {
    echo 'single view';
}

echo $OUTPUT->footer();