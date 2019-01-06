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
use runnerupweb\common\UserManager;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\User;

include_once('../../../../include/header_session.php');

header('Content-type: application/json');

try {
    // get the json options
    $request = file_get_contents('php://input');
    $newUser = User::userWithJson($request);
    $um = UserManager::getUserManager();
    if (!$newUser->checkUser(false)) {
        // user is not properly filled => error
         echo json_encode(LoginResponse::responseKo(1, "runnerupweb.user.not.properly.filled"), JSON_PRETTY_PRINT);
    } else {
        $oldUser = $um->getUser($newUser->getLogin());
        if ($oldUser) {
            // it is an update
            if ($newUser->getLogin() === $user->getLogin()) {
                // user trying to change himself
                if ($newUser->getRole() === 'ADMIN' && $oldUser->getRole() !== 'ADMIN' && $user->getRole()!== 'ADMIN') {
                    echo json_encode(LoginResponse::responseKo(2, "runnerupweb.nonadmin.change.role"), JSON_PRETTY_PRINT);
                } else {
                    $um->updateUser($newUser);
                    Logging::debug("User modified by himself", [$user]);
                    echo json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT);
                }
            } else if ($user->getRole() === 'ADMIN') {
                // admin change
                $um->updateUser($newUser);
                Logging::debug("User modified by an admin", [$user]);
                echo json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT);
            } else {
                echo json_encode(LoginResponse::responseKo(3, "runnerupweb.nonadmin.change.another.user"), JSON_PRETTY_PRINT);
            }
        } else {
            // it is a creation
            if ($user->getRole() === 'ADMIN') {
                if ($newUser->checkUser(true)) {
                    // create the user cos it is an admin logged
                    $um->createUser($newUser);
                    Logging::debug("User created", [$user]);
                    echo json_encode(LoginResponse::responseOk(), JSON_PRETTY_PRINT);
                } else {
                    echo json_encode(LoginResponse::responseKo(4, "runnerupweb.user.not.properly.filled.creation"), JSON_PRETTY_PRINT);
                }
            } else {
                // not allowed
                echo json_encode(LoginResponse::responseKo(5, "runnerupweb.nonadmin.create.user"), JSON_PRETTY_PRINT);
            }
        }
    }
} catch (Exception $ex) {
    Logging::error("Error performing the search", array($ex));
    echo json_encode(LoginResponse::responseKo(6, $ex->getMessage()), JSON_PRETTY_PRINT);
}
