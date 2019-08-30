<?php

/* 
 * Copyright (C) 2019 <https://github.com/rickyepoderi/runnerupweb>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require __DIR__ . '/../../../../bootstrap.php';

use runnerupweb\data\LoginResponse;
use runnerupweb\common\ActivityManager;
use runnerupweb\common\Logging;
use runnerupweb\common\WebUtils;

$user = WebUtils::checkUserSession('POST');
$id = WebUtils::getCompulsoryInt('id', 1, 1);
$delete = WebUtils::getBooleanDefaultFalse('delete', 2);

// start doing the search
try {
    $am = ActivityManager::getActivityManager();

    $activity = $am->getActivity($user->getLogin(), $id);
    if (isset($activity)) {
        $am->recalculateTagsInActivity($user->getLogin(), $activity->getId(), $delete);
        exit(json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT));
    } else {
        exit(json_encode(LoginResponse::responseKo(3, "runnerupweb.activity.does.not.exist"), JSON_PRETTY_PRINT));
    }
} catch(Exception $ex) {
    Logging::error("Error performing the search", array($ex));
    exit(json_encode(LoginResponse::responseKo(4, $ex->getMessage()), JSON_PRETTY_PRINT));
}