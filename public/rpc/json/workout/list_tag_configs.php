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
use runnerupweb\data\TagListResponse;
use runnerupweb\common\TagManager;
use runnerupweb\common\Logging;
use runnerupweb\common\WebUtils;

$user = WebUtils::checkUserSession('GET');

$tm = TagManager::getTagManager();

// start doing the search
try {
    $tags = $tm->getAllTags($user->getLogin());
    Logging::debug("Found tags: " . count($tags));
    echo json_encode(new TagListResponse($tags), JSON_PRETTY_PRINT);
} catch(Exception $ex) {
    Logging::error("Error performing the search", array($ex));
    echo json_encode(LoginResponse::responseKo(1, $ex->getMessage()), JSON_PRETTY_PRINT);
}