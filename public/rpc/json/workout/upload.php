<?php

/*
 * Copyright (c) 2016 ricky <https://github.com/rickyepoderi/runnerupweb>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__ . '/../../../../bootstrap.php';

use runnerupweb\common\Logging;
use runnerupweb\common\ActivityManager;

include_once('../../../../include/header_session.php');

Logging::debug("Files: ", $_FILES);

// the file is sent using a multipart => check that $_FILES is ok
if (!isset($_FILES['userFiles']['error']) || is_array($_FILES['userFiles']['error'])) {
    Logging::warning('Invalid parameters.');
    throw new RuntimeException('Invalid parameters.');
}
// check the file is correctly uploaded
switch ($_FILES['userFiles']['error']) {
    case UPLOAD_ERR_OK:
        break;
    case UPLOAD_ERR_NO_FILE:
        Logging::warning('No file sent.');
        throw new RuntimeException('No file sent.');
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_FORM_SIZE:
        Logging::warning('Exceeded filesize limit.');
        throw new RuntimeException('Exceeded filesize limit.');
    default:
        Logging::warning('Unknown errors.');
        throw new RuntimeException('Unknown errors.');
}
// check is a XML file
$finfo = new finfo(FILEINFO_MIME_TYPE);
if ($finfo->file($_FILES['userFiles']['tmp_name']) === 'application/xml' ||
        $finfo->file($_FILES['userFiles']['tmp_name']) === 'text/xml') {
    Logging::debug($finfo->file($_FILES['userFiles']['tmp_name']));
    // upload the TCX file into the application for the current user
    $am = ActivityManager::getActivityManager();
    //copy($_FILES['userFiles']['tmp_name'], "/tmp/lala");
    $am->storeActivities($user->getLogin(), $_FILES['userFiles']['tmp_name'], $_FILES['userFiles']['name']);
} else {
    Logging::debug("Invalid mime: " . $finfo->file($_FILES['userFiles']['tmp_name']));
    throw new RuntimeException('Invalid file format!');
}