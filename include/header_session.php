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

use runnerupweb\common\Logging;
use \runnerupweb\common\Configuration;
use \runnerupweb\data\User;

// add a header protection for everything
header("Content-Security-Policy: default-src 'self'");
// init the configuration
$config = Configuration::getConfiguration();
// start the session
session_start();
if (session_status() != PHP_SESSION_ACTIVE || !array_key_exists('login', $_SESSION)
        || !($_SESSION['login'] instanceof User) || $_SESSION['login']->getLogin() == null) {
    // user not authenticated => 403
    Logging::debug("User not authenticated!");
    header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . ' 403 Forbidden');
    exit;
} else {
    // put a variable with the user
    $user = $_SESSION['login'];
}
