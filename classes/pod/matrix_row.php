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
 * Contains class matrix_row
 *
 * @package    block_question_report
 * @copyright  2022 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

namespace block_question_report\pod;

class matrix_row {
    public $id;
    public $fraction;
    public $name;
    public $feedback;

    /**
     * @param int    $id
     * @param float  $fraction
     * @param string $name
     * @param string $feedback
     */
    public function __construct(int $id, float $fraction, string $name, string $feedback) {
        $this->id = $id;
        $this->fraction = $fraction;
        $this->name = $name;
        $this->feedback = $feedback;
    }

}
