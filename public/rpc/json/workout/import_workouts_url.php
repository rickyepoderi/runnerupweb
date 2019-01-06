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

include_once('../../../../include/header_session.php');

// for the moment return a fixed URL
Logging::debug("calling import_workouts_url");
header("Content-type: application/json");
echo "{\"response\":{\"upload_url\": {\"URL\": \"" . $_SERVER['REQUEST_SCHEME'] ."://" . $_SERVER['SERVER_NAME'] . "/rpc/json/workout/upload.php\"}}}";

