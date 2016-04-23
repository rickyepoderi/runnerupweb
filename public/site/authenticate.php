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

require __DIR__ . '/../../bootstrap.php';

use runnerupweb\common\Configuration;
use runnerupweb\common\Logging;
use runnerupweb\common\UserManager;
use runnerupweb\data\LoginResponse;
use runnerupweb\data\UserResponse;
use runnerupweb\data\User;

try {
    $config = Configuration::getConfiguration();
    session_start();
    // destroy the current session
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }
    // do the rest
    $request = file_get_contents('php://input');
    $input = json_decode($request);
    Logging::debug("trying to autenticate: ", array($input));
    $user = User::userWithJson($request);
    Logging::debug("login info: ", array($user));
    // set the header
    if (filter_input(INPUT_GET, 'type') === 'json') {
        header("Content-type: application/json");
    } else {
        header("Content-type: application/xml");
    }
    // validate the user and password using the manager
    $res = null;
    if ($user->getLogin() && $user->getPassword()) {
        $um = UserManager::getUserManager();
        $user = $um->checkUserPassword($user->getLogin(), $user->getPassword());
        if ($user != null) {
            // store the user into the session for further requests
            session_start();
            $_SESSION['login'] = $user;
            Logging::debug("Login OK!");
            $res = LoginResponse::responseOk();
        } else {
            Logging::debug("Invalid username or password");
            $res = LoginResponse::responseKo(1102, "Invalid username or password!");
        }
    } else {
        Logging::debug("Invalid data");
        $res = LoginResponse::responseKo(1101, "The json data provided is incorrect!");
    }
    if (filter_input(INPUT_GET, 'type') === 'json') {
        // in case of json => return the user logged in also in the response if ok
        if ($user && $res->isSuccess()) {
            echo json_encode(new UserResponse($user), JSON_PRETTY_PRINT);
        } else {
            echo json_encode($res->jsonSerialize(), JSON_PRETTY_PRINT);
        }
    } else {
        echo $res->toXml();
    }
} catch (Exception $ex) {
    Logging::error("Error performing the login", array($ex));
    echo json_encode(LoginResponse::responseKo(5, $ex->getMessage()), JSON_PRETTY_PRINT);
}
