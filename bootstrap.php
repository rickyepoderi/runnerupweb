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

// third party modules
require_once __DIR__ . '/vendor/autoload.php';

// common
require_once __DIR__ . '/common/Logging.php';
require_once __DIR__ . '/common/DataBase.php';
require_once __DIR__ . '/common/UserManager.php';
require_once __DIR__ . '/common/ActivityManager.php';
require_once __DIR__ . '/common/TCXManager.php';
require_once __DIR__ . '/common/UserOptionManager.php';
require_once __DIR__ . '/common/VersionManager.php';
require_once __DIR__ . '/common/Configuration.php';
require_once __DIR__ . '/common/WebUtils.php';
require_once __DIR__ . '/common/Client.php';

// data
require_once __DIR__ . '/data/Activity.php';
require_once __DIR__ . '/data/ActivityLap.php';
require_once __DIR__ . '/data/LoginResponse.php';
require_once __DIR__ . '/data/User.php';
require_once __DIR__ . '/data/UserResponse.php';
require_once __DIR__ . '/data/UserSearchResponse.php';
require_once __DIR__ . '/data/ActivitySearchResponse.php';
require_once __DIR__ . '/data/UserOption.php';
require_once __DIR__ . '/data/UserOptionResponse.php';
