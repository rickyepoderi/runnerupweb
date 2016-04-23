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

use runnerupweb\data\LoginResponse;
use runnerupweb\data\ActivitySearchResponse;
use runnerupweb\common\ActivityManager;
use runnerupweb\common\Logging;
use runnerupweb\common\WebUtils;

include_once('../../../../include/header_session.php');

$am = ActivityManager::getActivityManager();
// the result is json
header('Content-type: application/json');
// read the parameters
$start = WebUtils::getCompulsoryDateTime('start', 1);
$end = WebUtils::getOptionalDateTime('end', 2);
$offset = WebUtils::getOptionalInt('offset', 3, 0);
$limit = WebUtils::getOptionalInt('limit', 4, 1, $am->getMaxRows());
// start doing the search
try {
    $activities = $am->searchActivities($user->getLogin(), $start, $end, $offset, $limit);
    Logging::debug("Found activities: " . count($activities));
    echo json_encode(new ActivitySearchResponse($activities), JSON_PRETTY_PRINT);
} catch(Exception $ex) {
    Logging::error("Error performing the search", array($ex));
    echo json_encode(LoginResponse::responseKo(5, $ex->getMessage()), JSON_PRETTY_PRINT);
}