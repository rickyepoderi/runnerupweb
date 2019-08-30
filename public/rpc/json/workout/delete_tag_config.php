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

$user = WebUtils::checkUserSession('POST');
$tag = WebUtils::getCompulsoryString('tag', 1);
Logging::debug("deleting tag_config $tag for " . $user->getLogin());

try {
  $tm = TagManager::getTagManager();
  $deleted = $tm->deleteTagConfig($user->getLogin(), $tag);
  if (!$deleted) {
      echo json_encode(LoginResponse::responseKo(2, "runnerupweb.tag.does.not.exist"), JSON_PRETTY_PRINT);
  } else {
      echo json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT);
  }
} catch(Exception $ex) {
    Logging::error("Error performing the delete", array($ex));
    echo json_encode(LoginResponse::responseKo(3, $ex->getMessage()), JSON_PRETTY_PRINT);
}