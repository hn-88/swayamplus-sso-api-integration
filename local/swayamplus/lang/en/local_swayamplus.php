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
 * Strings for component 'local_swayamplus'
 *
 * @package    local_swayamplus
 * @copyright  2025 hn_88
 * @license    https://mit-license.org/
 */

defined('MOODLE_INTERNAL') || die();

// Required by every plugin - used wherever Moodle lists installed plugins
// (admin/plugins.php, the localplugins settings tree, etc.).
$string['pluginname'] = 'Swayam Plus';

// --- Settings page strings (settings.php) ---
$string['settingspage'] = 'Swayam Plus Settings';

$string['oauthissuerid'] = 'OAuth 2 Issuer ID';
$string['oauthissuerid_desc'] = 'Enter the ID number of the Swayam Mock / Production service created in OAuth 2 Services.';

$string['swayamurl'] = 'Swayam API Base URL';
$string['swayamurl_desc'] = 'e.g. https://a1b2-c3d4.ngrok-free.app';

// --- Likely needed elsewhere in this plugin ---

// Scheduled task display name, referenced from db/tasks.php as
// 'local_swayamplus\task\sync_roster'.
$string['synctask'] = 'Swayam Plus roster sync';

// If you add db/access.php with a capability such as
// 'local/swayamplus:manage':
// $string['swayamplus:manage'] = 'Manage Swayam Plus settings';

// Privacy API stub, required if you later add classes/privacy/provider.php.
// $string['privacy:metadata'] = 'The Swayam Plus plugin does not store personal data.';
