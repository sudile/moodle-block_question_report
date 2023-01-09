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
 * @copyright  2023 sudile GbR (http://www.sudile.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Vincent Schneider <vincent.schneider@sudile.com>
 */

export const init = () => {
    let downloadButton = document.getElementById('download-graph');
    if (downloadButton !== null) {
        downloadButton.addEventListener('click', function(e) {
            e.preventDefault();
            let downloadLink = document.createElement('a');
            let name = this.getAttribute('data-name') + '_' + this.getAttribute('data-attemptid') + '.png';
            downloadLink.setAttribute('download', name);
            let canvas = document.querySelector('.chart-image canvas');
            let dataUrl = canvas.toDataURL('image/png');
            let url = dataUrl.replace(/^data:image\/png/, 'data:application/octet-stream');
            downloadLink.setAttribute('href', url);
            downloadLink.click();
        });
    }

};