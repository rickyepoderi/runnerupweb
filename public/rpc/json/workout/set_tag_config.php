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

use runnerupweb\common\Logging;
use runnerupweb\common\TagManager;
use runnerupweb\common\WebUtils;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\TagConfig;

$user = WebUtils::checkUserSession('POST');
$op = WebUtils::getCompulsoryStringInValues('mode', ['create', 'edit'], 1);

try {
    // get the json options
    $request = file_get_contents('php://input');
    $newTagConfig = TagConfig::tagConfigWithJson($request);
    if ($newTagConfig->isAuto()) {
        $provider = $newTagConfig->getProvider();
        $autoTag = new $provider();
        $res = $autoTag->convertExtraToConfig($newTagConfig);
        if (!is_null($res)) {
            exit(json_encode(LoginResponse::responseKo(2, $res), JSON_PRETTY_PRINT));
        }
    }
    // ckect the tag manager
    if (!$newTagConfig->check()) {
        exit(json_encode(LoginResponse::responseKo(3, 'runnerupweb.tag.not.properly.filled'), JSON_PRETTY_PRINT));
    }
    // check if the tag config already exists
    $tm = TagManager::getTagManager();
    $oldTagConfig = $tm->getTagConfig($user->getLogin(), $newTagConfig->getTag());
    if ($op === 'create') {
        if (is_null($oldTagConfig)) {
            $tm->createTagConfig($user->getLogin(), $newTagConfig);
            exit(json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT));
        } else {
            exit(json_encode(LoginResponse::responseKo(4, 'runnerupweb.tag.already.exists'), JSON_PRETTY_PRINT));
        }
    } else {
        if (is_null($oldTagConfig)) {
            exit(json_encode(LoginResponse::responseKo(5, 'runnerupweb.tag.does.not.exist'), JSON_PRETTY_PRINT));
        } else {
            $tm->updateTagConfig($user->getLogin(), $newTagConfig);
            exit(json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT));
        }
    }
} catch(Throwable $ex) {
    Logging::error("Error performing the search", array($ex));
    echo json_encode(LoginResponse::responseKo(6, $ex->getMessage()), JSON_PRETTY_PRINT);
}