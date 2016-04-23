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
use runnerupweb\data\UserOption;
use runnerupweb\common\UserOptionManager;
use runnerupweb\data\LoginResponse;

include_once('../../../../include/header_session.php');

header('Content-type: application/json');

try {
    // get the json options
    $request = file_get_contents('php://input');
    // get the options and save them into the database
    $uom = UserOptionManager::getUserOptionManager();
    $opts = UserOption::userOptionWithJson($request);
    if ($uom->check($opts)) {
        $uom->set($user->getLogin(), $opts);
        Logging::debug("Options saved", [$opts]);
        echo json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT);
    } else {
        Logging::error("The options are not valid", [$opts]);
        echo json_encode(LoginResponse::responseKo(1, "The options are not valid"), JSON_PRETTY_PRINT);
    }
} catch (Exception $ex) {
    Logging::error("Error performing the search", array($ex));
    echo json_encode(LoginResponse::responseKo(2, "Internal server error"), JSON_PRETTY_PRINT);
}
